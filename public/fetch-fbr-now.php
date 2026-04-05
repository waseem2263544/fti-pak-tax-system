<?php
set_time_limit(120);
echo "<pre><h2>Manual FBR Notice Fetch</h2>\n";

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MicrosoftEmailSettings;
use App\Models\FbrNotice;
use Illuminate\Support\Facades\Http;

$settings = MicrosoftEmailSettings::first();
if (!$settings) { die("No email connected.\n"); }

echo "Email: {$settings->email_address}\n";
echo "FBR Sender filter: {$settings->fbr_sender_email}\n";
echo "Token expires: {$settings->token_expires_at}\n\n";

// First, let's see ALL recent emails (not just FBR)
echo "=== Last 10 emails in inbox ===\n\n";

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $settings->access_token,
])->get('https://graph.microsoft.com/v1.0/me/mailfolders/inbox/messages', [
    '$select' => 'id,subject,receivedDateTime,from',
    '$top' => 10,
    '$orderby' => 'receivedDateTime desc',
]);

if (!$response->successful()) {
    die("API Error: " . $response->body() . "\n");
}

$emails = $response->json()['value'] ?? [];
foreach ($emails as $i => $email) {
    $from = $email['from']['emailAddress']['address'] ?? 'unknown';
    $subject = $email['subject'] ?? 'No subject';
    $date = $email['receivedDateTime'] ?? '';
    echo ($i+1) . ". FROM: $from\n   SUBJECT: $subject\n   DATE: $date\n\n";
}

// Now try to fetch FBR-specific emails
echo "\n=== Emails from FBR ({$settings->fbr_sender_email}) ===\n\n";

$fbrResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $settings->access_token,
])->get('https://graph.microsoft.com/v1.0/me/mailfolders/inbox/messages', [
    '$filter' => "from/emailAddress/address eq '{$settings->fbr_sender_email}'",
    '$select' => 'id,subject,receivedDateTime,from',
    '$top' => 10,
    '$orderby' => 'receivedDateTime desc',
]);

if (!$fbrResponse->successful()) {
    echo "Filter error: " . $fbrResponse->body() . "\n";
    echo "\nTrying without filter...\n";
} else {
    $fbrEmails = $fbrResponse->json()['value'] ?? [];
    if (empty($fbrEmails)) {
        echo "No emails found from {$settings->fbr_sender_email}\n";
        echo "Check if FBR sends from a different email address.\n";
        echo "You can update the sender email in Settings > Email Integration.\n";
    } else {
        foreach ($fbrEmails as $i => $email) {
            echo ($i+1) . ". " . $email['subject'] . " (" . $email['receivedDateTime'] . ")\n";
        }
    }
}

echo "\nExisting notices in DB: " . FbrNotice::count() . "\n";
echo "</pre>";
