<?php

namespace App\Http\Controllers;

use App\Models\FbrNotice;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class FbrNoticeController extends Controller
{
    public function index(Request $request)
    {
        $query = FbrNotice::with('client');

        // Default: show only unread + read (needs attention)
        $filter = $request->get('filter', 'pending');

        if ($filter === 'pending') {
            $query->whereIn('status', ['new', 'reviewed']);
        } elseif ($filter === 'actioned') {
            $query->whereIn('status', ['resolved', 'escalated']);
        }
        // 'all' shows everything

        if ($request->filled('tax_year')) {
            $query->where('tax_year', $request->tax_year);
        }

        if ($request->filled('section')) {
            $query->where('notice_section', $request->section);
        }

        $notices = $query->orderBy('email_received_at', 'desc')->paginate(20);

        $taxYears = FbrNotice::distinct('tax_year')->whereNotNull('tax_year')->pluck('tax_year');
        $sections = FbrNotice::distinct('notice_section')->whereNotNull('notice_section')->pluck('notice_section');

        $pendingCount = FbrNotice::whereIn('status', ['new', 'reviewed'])->count();
        $actionedCount = FbrNotice::whereIn('status', ['resolved', 'escalated'])->count();

        return view('fbr-notices.index', compact('notices', 'taxYears', 'sections', 'filter', 'pendingCount', 'actionedCount'));
    }

    public function show(FbrNotice $fbrNotice)
    {
        $fbrNotice->load('client', 'escalatedTo');

        // Auto-mark as read when opened
        if ($fbrNotice->status === 'new') {
            $fbrNotice->update(['status' => 'reviewed']);
        }

        return view('fbr-notices.show', compact('fbrNotice'));
    }

    public function updateStatus(Request $request, FbrNotice $fbrNotice)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,reviewed,resolved,escalated',
        ]);

        $fbrNotice->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Notice status updated');
    }

    public function escalate(Request $request, FbrNotice $fbrNotice)
    {
        $validated = $request->validate([
            'escalated_to' => 'required|exists:users,id',
        ]);

        $fbrNotice->update([
            'is_escalated' => true,
            'escalated_to' => $validated['escalated_to'],
            'status' => 'escalated',
        ]);

        return redirect()->back()->with('success', 'Notice escalated successfully');
    }

    public function assignClient(Request $request, FbrNotice $fbrNotice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        $fbrNotice->update(['client_id' => $validated['client_id']]);

        return redirect()->back()->with('success', 'Notice assigned to client');
    }

    public function dismiss(FbrNotice $fbrNotice)
    {
        $fbrNotice->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Notice dismissed');
    }

    public function markRead(FbrNotice $fbrNotice)
    {
        if ($fbrNotice->status === 'new') {
            $fbrNotice->update(['status' => 'reviewed']);
        }
        return response()->json(['success' => true]);
    }

    public function fetchNow()
    {
        $settings = \App\Models\MicrosoftEmailSettings::first();
        if (!$settings) {
            return redirect()->route('fbr-notices.index')->with('error', 'No email connected. Go to Settings > Email Integration.');
        }

        // Auto-refresh token if expired
        if ($settings->isTokenExpired()) {
            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => env('MICROSOFT_CLIENT_ID', ''),
                'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
                'refresh_token' => $settings->refresh_token,
                'grant_type' => 'refresh_token',
                'scope' => 'openid profile email Mail.Read offline_access',
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $settings->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $settings->refresh_token,
                    'token_expires_at' => \Carbon\Carbon::now()->addSeconds($data['expires_in']),
                ]);
                $settings->refresh();
            } else {
                return redirect()->route('fbr-notices.index')->with('error', 'Token refresh failed. Please reconnect at Settings > Email Integration.');
            }
        }

        // Fetch emails
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->access_token,
        ])->get('https://graph.microsoft.com/v1.0/me/mailfolders/inbox/messages', [
            '$select' => 'id,subject,bodyPreview,body,receivedDateTime,from',
            '$top' => 50,
            '$orderby' => 'receivedDateTime desc',
        ]);

        if (!$response->successful()) {
            return redirect()->route('fbr-notices.index')->with('error', 'Failed to fetch emails from Microsoft.');
        }

        $emails = $response->json()['value'] ?? [];
        $imported = 0;

        foreach ($emails as $email) {
            $from = strtolower($email['from']['emailAddress']['address'] ?? '');
            if (strpos($from, 'fbr.gov.pk') === false) continue;
            if (stripos($email['subject'] ?? '', 'Format for Sub User DI CRM') !== false) continue;
            if (FbrNotice::where('email_message_id', $email['id'])->exists()) continue;

            $subject = $email['subject'] ?? '';
            $bodyPreview = $email['bodyPreview'] ?? '';
            $bodyContent = $email['body']['content'] ?? $bodyPreview;
            $receivedDate = \Carbon\Carbon::parse($email['receivedDateTime']);

            // Extract section
            $noticeSection = 'General';
            $patterns = ['/122\(9\)/i'=>'Section 122(9)','/122\(5A\)/i'=>'Section 122(5A)','/114\(\d+\)/i'=>'Section 114','/111\(\d+\)/i'=>'Section 111','/137\(\d+\)/i'=>'Section 137','/205/i'=>'Section 205','/CVT/i'=>'CVT','/Income Tax/i'=>'Income Tax','/Sales Tax/i'=>'Sales Tax','/Assessment/i'=>'Assessment','/Audit/i'=>'Audit'];
            foreach ($patterns as $p => $s) { if (preg_match($p, $subject)) { $noticeSection = $s; break; } }

            // Extract tax year
            $taxYear = null;
            if (preg_match('/(\d{4})-(\d{2,4})/', $bodyPreview . $bodyContent, $m)) { $taxYear = $m[1].'-'.$m[2]; }
            else { $y = now()->year; if (now()->month < 7) $y--; $taxYear = $y.'-'.str_pad($y+1-2000,2,'0',STR_PAD_LEFT); }

            // Match client
            $clientId = null;
            $allContent = $subject.' '.$bodyPreview.' '.$bodyContent;
            foreach (\App\Models\Client::all() as $c) {
                if (!empty($c->fbr_username) && stripos($allContent, $c->fbr_username) !== false) { $clientId = $c->id; break; }
                if (stripos($allContent, $c->name) !== false) { $clientId = $c->id; break; }
            }

            $notice = FbrNotice::create([
                'email_message_id' => $email['id'], 'subject' => $subject, 'body' => $bodyPreview,
                'raw_content' => $bodyContent, 'notice_section' => $noticeSection, 'tax_year' => $taxYear,
                'notice_date' => $receivedDate->toDateString(), 'email_received_at' => $receivedDate->toDateString(),
                'sender_email' => $from, 'client_id' => $clientId, 'status' => 'new',
            ]);

            // Notify admins
            $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id, 'client_id' => $clientId,
                    'title' => 'New FBR Notice: ' . $noticeSection,
                    'message' => 'Subject: ' . $subject . ' (Tax Year: ' . $taxYear . ')',
                    'type' => 'fbr_notice', 'priority' => 'high', 'related_fbr_notice_id' => $notice->id,
                ]);
            }
            $imported++;
        }

        $settings->update(['last_synced_at' => now()]);

        if ($imported > 0) {
            return redirect()->route('fbr-notices.index')->with('success', "Fetched $imported new FBR notification" . ($imported > 1 ? 's' : '') . ".");
        }
        return redirect()->route('fbr-notices.index')->with('success', 'No new FBR notifications found.');
    }

    public function download(FbrNotice $fbrNotice)
    {
        return response()->json([
            'subject' => $fbrNotice->subject,
            'body' => $fbrNotice->body,
            'raw_content' => $fbrNotice->raw_content,
        ]);
    }
}
