<?php

namespace App\Http\Controllers;

use App\Models\MicrosoftEmailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MicrosoftAuthController extends Controller
{
    private function getConfig()
    {
        return [
            'client_id' => env('MICROSOFT_CLIENT_ID', ''),
            'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
            'redirect_uri' => env('MICROSOFT_REDIRECT_URI', 'https://app.fairtaxint.com/auth/microsoft/callback'),
            'tenant' => 'common',
        ];
    }

    public function redirect()
    {
        $config = $this->getConfig();
        $url = "https://login.microsoftonline.com/{$config['tenant']}/oauth2/v2.0/authorize?" . http_build_query([
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $config['redirect_uri'],
            'scope' => 'openid profile email Mail.Read offline_access',
            'response_mode' => 'query',
            'state' => csrf_token(),
        ]);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('settings.email')->with('error', 'Microsoft authorization failed: ' . $request->error_description);
        }

        $config = $this->getConfig();

        // Exchange code for tokens
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$config['tenant']}/oauth2/v2.0/token", [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $request->code,
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
            'scope' => 'openid profile email Mail.Read offline_access',
        ]);

        if (!$response->successful()) {
            return redirect()->route('settings.email')->with('error', 'Failed to get access token');
        }

        $data = $response->json();

        // Get user email from Microsoft
        $profileResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $data['access_token'],
        ])->get('https://graph.microsoft.com/v1.0/me');

        $profile = $profileResponse->json();
        $email = $profile['mail'] ?? $profile['userPrincipalName'] ?? 'unknown';

        // Save or update settings
        MicrosoftEmailSettings::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? '',
                'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                'email_address' => $email,
                'fbr_sender_email' => 'no-reply@fbr.gov.pk',
            ]
        );

        return redirect()->route('settings.email')->with('success', 'Microsoft email connected successfully! Email: ' . $email);
    }

    public function disconnect()
    {
        MicrosoftEmailSettings::where('user_id', auth()->id())->delete();
        return redirect()->route('settings.email')->with('success', 'Microsoft email disconnected');
    }

    public function refreshToken()
    {
        $settings = MicrosoftEmailSettings::where('user_id', auth()->id())->first();
        if (!$settings) {
            return redirect()->route('settings.email')->with('error', 'No email connected');
        }

        $config = $this->getConfig();

        $response = Http::asForm()->post("https://login.microsoftonline.com/{$config['tenant']}/oauth2/v2.0/token", [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'refresh_token' => $settings->refresh_token,
            'grant_type' => 'refresh_token',
            'scope' => 'openid profile email Mail.Read offline_access',
        ]);

        if (!$response->successful()) {
            return redirect()->route('settings.email')->with('error', 'Token refresh failed. Please reconnect your Microsoft account.');
        }

        $data = $response->json();
        $settings->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $settings->refresh_token,
            'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
        ]);

        return redirect()->route('settings.email')->with('success', 'Token refreshed successfully! New expiry: ' . Carbon::now()->addSeconds($data['expires_in'])->format('M d, Y H:i'));
    }

    public function testFetch()
    {
        $settings = MicrosoftEmailSettings::where('user_id', auth()->id())->first();

        if (!$settings) {
            return redirect()->route('settings.email')->with('error', 'No email connected');
        }

        // Auto-refresh if expired
        if ($settings->isTokenExpired()) {
            $config = $this->getConfig();
            $response = Http::asForm()->post("https://login.microsoftonline.com/{$config['tenant']}/oauth2/v2.0/token", [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $settings->refresh_token,
                'grant_type' => 'refresh_token',
                'scope' => 'openid profile email Mail.Read offline_access',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $settings->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $settings->refresh_token,
                    'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                ]);
                $settings->refresh();
            } else {
                return redirect()->route('settings.email')->with('error', 'Token expired and refresh failed. Please reconnect.');
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->access_token,
        ])->get('https://graph.microsoft.com/v1.0/me/mailfolders/inbox/messages', [
            '$top' => 5,
            '$select' => 'subject,receivedDateTime,from',
            '$orderby' => 'receivedDateTime desc',
        ]);

        if (!$response->successful()) {
            return redirect()->route('settings.email')->with('error', 'Failed to fetch emails. Try reconnecting.');
        }

        $emails = $response->json()['value'] ?? [];
        return redirect()->route('settings.email')->with('success', 'Connection working! Latest ' . count($emails) . ' emails fetched. Token valid until ' . $settings->token_expires_at->format('M d, H:i'));
    }
}
