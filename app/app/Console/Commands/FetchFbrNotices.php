<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MicrosoftEmailSettings;
use App\Models\FbrNotice;
use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FetchFbrNotices extends Command
{
    protected $signature = 'app:fetch-fbr-notices';
    protected $description = 'Fetch FBR notices from Microsoft email account via Graph API';

    public function handle()
    {
        $this->info('Starting FBR notice fetching...');

        // Get admin user with Microsoft email settings
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        if (!$admin || !$admin->microsoftEmailSettings) {
            $this->warn('No admin user with Microsoft email settings configured');
            return;
        }

        $settings = $admin->microsoftEmailSettings;

        // Refresh token if needed
        if ($settings->isTokenExpired()) {
            $this->refreshAccessToken($settings);
        }

        // Fetch emails from FBR
        $this->fetchAndProcessEmails($settings, $admin);

        $this->info('FBR notice fetching completed');
    }

    private function refreshAccessToken($settings)
    {
        try {
            $response = Http::post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'refresh_token' => $settings->refresh_token,
                'grant_type' => 'refresh_token',
                'scope' => 'Mail.Read',
            ]);

            $data = $response->json();
            $settings->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? $settings->refresh_token,
                'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh Microsoft token: ' . $e->getMessage());
        }
    }

    private function fetchAndProcessEmails($settings, $user)
    {
        try {
            // Fetch emails from FBR sender
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $settings->access_token,
            ])->get('https://graph.microsoft.com/v1.0/me/mailfolders/inbox/messages', [
                '$filter' => "from/emailAddress/address eq '{$settings->fbr_sender_email}'",
                '$select' => 'id,subject,bodyPreview,receivedDateTime,sender,body',
                '$top' => 50,
                '$orderby' => 'receivedDateTime desc',
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch emails from Graph API: ' . $response->body());
                return;
            }

            $emails = $response->json()['value'] ?? [];

            foreach ($emails as $email) {
                $this->processEmail($email, $user, $settings);
            }

            $settings->update(['last_synced_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Error fetching FBR notices: ' . $e->getMessage());
        }
    }

    private function processEmail($email, $user, $settings)
    {
        // Check if notice already exists
        $existingNotice = FbrNotice::where('email_message_id', $email['id'])->first();
        if ($existingNotice) {
            return;
        }

        // Parse notice section from subject
        $noticeSection = $this->extractNoticeSection($email['subject']);
        $taxYear = $this->extractTaxYear($email['body'] ?? $email['bodyPreview'] ?? '');

        // Try to auto-assign to client if mentioned in subject/body
        $clientId = $this->findRelatedClient($email['subject'], $email['body'] ?? '');

        // Create notice record
        $notice = FbrNotice::create([
            'email_message_id' => $email['id'],
            'subject' => $email['subject'],
            'body' => $email['bodyPreview'] ?? '',
            'raw_content' => $email['body'] ?? '',
            'notice_section' => $noticeSection,
            'tax_year' => $taxYear,
            'notice_date' => Carbon::parse($email['receivedDateTime'])->toDateString(),
            'email_received_at' => Carbon::parse($email['receivedDateTime'])->toDateString(),
            'sender_email' => $email['sender']['emailAddress']['address'] ?? $settings->fbr_sender_email,
            'client_id' => $clientId,
            'status' => 'new',
        ]);

        // Create notification for admins
        $this->notifyAdmins($notice, $user);
    }

    private function extractNoticeSection($subject)
    {
        $patterns = [
            '/Income Tax/i' => 'Income Tax',
            '/Sales Tax/i' => 'Sales Tax',
            '/KPRA/i' => 'KPRA',
            '/SECP/i' => 'SECP',
            '/Withholding/i' => 'Withholding Tax',
            '/GST/i' => 'GST',
            '/Assessment/i' => 'Assessment',
            '/Audit/i' => 'Audit',
            '/Notice/i' => 'General Notice',
        ];

        foreach ($patterns as $pattern => $section) {
            if (preg_match($pattern, $subject)) {
                return $section;
            }
        }

        return 'General';
    }

    private function extractTaxYear($content)
    {
        // Try to extract tax year from content (e.g., "2024-25", "FY2024-25")
        if (preg_match('/(\d{4})-(\d{2,4})/', $content, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        // Default to current tax year
        $month = now()->month;
        $year = now()->year;
        if ($month < 7) {
            $year--;
        }

        return ($year) . '-' . str_pad($year + 1 - 2000, 2, '0', STR_PAD_LEFT);
    }

    private function findRelatedClient($subject, $body)
    {
        $content = $subject . ' ' . $body;
        $clients = Client::all();

        foreach ($clients as $client) {
            if (stripos($content, $client->name) !== false) {
                return $client->id;
            }
        }

        return null;
    }

    private function notifyAdmins($notice, $user)
    {
        $admins = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'client_id' => $notice->client_id,
                'title' => 'New FBR Notice: ' . $notice->notice_section,
                'message' => 'Subject: ' . $notice->subject . ' (Tax Year: ' . $notice->tax_year . ')',
                'type' => 'fbr_notice',
                'priority' => 'high',
                'related_fbr_notice_id' => $notice->id,
            ]);
        }
    }
}
