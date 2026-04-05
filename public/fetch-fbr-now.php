<?php
set_time_limit(300);
echo "<pre><h2>Fetching FBR Notices</h2>\n";

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MicrosoftEmailSettings;
use App\Models\FbrNotice;
use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$settings = MicrosoftEmailSettings::first();
if (!$settings) { die("No email connected.\n"); }

// Auto-refresh token if expired
if ($settings->isTokenExpired()) {
    echo "Token expired, refreshing...\n";
    $config = [
        'client_id' => env('MICROSOFT_CLIENT_ID', ''),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
    ];
    $tokenResponse = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $settings->refresh_token,
        'grant_type' => 'refresh_token',
        'scope' => 'openid profile email Mail.Read offline_access',
    ]);
    if ($tokenResponse->successful()) {
        $tokenData = $tokenResponse->json();
        $settings->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $settings->refresh_token,
            'token_expires_at' => Carbon::now()->addSeconds($tokenData['expires_in']),
        ]);
        $settings->refresh();
        echo "Token refreshed!\n\n";
    } else {
        die("Token refresh failed. Please reconnect at Settings > Email Integration.\n" . $tokenResponse->body());
    }
}

echo "Email: {$settings->email_address}\n";
echo "FBR Sender: {$settings->fbr_sender_email}\n\n";

// Fetch last 50 emails (no filter - filter in PHP)
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $settings->access_token,
])->get('https://graph.microsoft.com/v1.0/me/mailfolders/inbox/messages', [
    '$select' => 'id,subject,bodyPreview,body,receivedDateTime,from',
    '$top' => 50,
    '$orderby' => 'receivedDateTime desc',
]);

if (!$response->successful()) {
    die("API Error: " . $response->body() . "\n");
}

$emails = $response->json()['value'] ?? [];
echo "Fetched " . count($emails) . " emails from inbox.\n\n";

$imported = 0;
$skipped = 0;
$existing = 0;

foreach ($emails as $email) {
    $from = strtolower($email['from']['emailAddress']['address'] ?? '');

    // Only process FBR emails
    if (strpos($from, 'fbr.gov.pk') === false) {
        $skipped++;
        continue;
    }

    // Skip known spam/system emails
    $subject = $email['subject'] ?? '';
    if (stripos($subject, 'Format for Sub User DI CRM') !== false) {
        $skipped++;
        continue;
    }

    // Check if already imported
    if (FbrNotice::where('email_message_id', $email['id'])->exists()) {
        $existing++;
        continue;
    }

    $subject = $email['subject'] ?? '';
    $bodyPreview = $email['bodyPreview'] ?? '';
    $bodyContent = $email['body']['content'] ?? $bodyPreview;
    $receivedDate = Carbon::parse($email['receivedDateTime']);

    // Extract notice section from subject
    $noticeSection = 'General';
    $patterns = [
        '/Income Tax/i' => 'Income Tax',
        '/Sales Tax/i' => 'Sales Tax',
        '/KPRA/i' => 'KPRA',
        '/SECP/i' => 'SECP',
        '/Withholding/i' => 'Withholding Tax',
        '/GST/i' => 'GST',
        '/Assessment/i' => 'Assessment',
        '/Audit/i' => 'Audit',
        '/122\(9\)/i' => 'Section 122(9)',
        '/122\(5A\)/i' => 'Section 122(5A)',
        '/114\(\d+\)/i' => 'Section 114',
        '/111\(\d+\)/i' => 'Section 111',
        '/137\(\d+\)/i' => 'Section 137',
        '/205/i' => 'Section 205',
        '/CVT/i' => 'CVT',
    ];
    foreach ($patterns as $pattern => $section) {
        if (preg_match($pattern, $subject)) {
            $noticeSection = $section;
            break;
        }
    }

    // Extract tax year
    $taxYear = null;
    if (preg_match('/(\d{4})-(\d{2,4})/', $bodyPreview . $bodyContent, $matches)) {
        $taxYear = $matches[1] . '-' . $matches[2];
    } else {
        $month = now()->month;
        $year = now()->year;
        if ($month < 7) $year--;
        $taxYear = $year . '-' . str_pad($year + 1 - 2000, 2, '0', STR_PAD_LEFT);
    }

    // Try to match client by NTN/name in subject or body
    $clientId = null;
    $allContent = $subject . ' ' . $bodyPreview . ' ' . $bodyContent;
    $clients = Client::all();
    foreach ($clients as $client) {
        if (!empty($client->fbr_username) && stripos($allContent, $client->fbr_username) !== false) {
            $clientId = $client->id;
            break;
        }
        if (stripos($allContent, $client->name) !== false) {
            $clientId = $client->id;
            break;
        }
    }

    // Create notice
    $notice = FbrNotice::create([
        'email_message_id' => $email['id'],
        'subject' => $subject,
        'body' => $bodyPreview,
        'raw_content' => $bodyContent,
        'notice_section' => $noticeSection,
        'tax_year' => $taxYear,
        'notice_date' => $receivedDate->toDateString(),
        'email_received_at' => $receivedDate->toDateString(),
        'sender_email' => $from,
        'client_id' => $clientId,
        'status' => 'new',
    ]);

    $clientName = $clientId ? Client::find($clientId)->name : 'Unassigned';
    echo "  IMPORTED: {$subject}\n";
    echo "    Section: {$noticeSection} | Tax Year: {$taxYear} | Client: {$clientName}\n\n";

    // Notify admins
    $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
    foreach ($admins as $admin) {
        Notification::create([
            'user_id' => $admin->id,
            'client_id' => $clientId,
            'title' => 'New FBR Notice: ' . $noticeSection,
            'message' => 'Subject: ' . $subject . ' (Tax Year: ' . $taxYear . ')',
            'type' => 'fbr_notice',
            'priority' => 'high',
            'related_fbr_notice_id' => $notice->id,
        ]);
    }

    $imported++;
}

$settings->update(['last_synced_at' => now()]);

echo "========================================\n";
echo "FETCH COMPLETE!\n";
echo "Imported: $imported\n";
echo "Already existed: $existing\n";
echo "Non-FBR emails skipped: $skipped\n";
echo "Total notices in DB: " . FbrNotice::count() . "\n";
echo "</pre>";
