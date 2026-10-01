<?php
/**
 * One-shot: set a single key in the server's .env.
 *
 * The value arrives in the POST body, not the query string, so it stays out of
 * the access log. The script carries no secret of its own, which is why it can
 * live in the repository at all.
 *
 * Neutralises itself afterwards, because opcache on this host keeps serving a
 * file that has merely been unlinked.
 */
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(404); exit("Not Found\n"); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit("POST only\n"); }

$key   = trim((string) ($_POST['key'] ?? ''));
$value = (string) ($_POST['value'] ?? '');

if (!preg_match('/^[A-Z][A-Z0-9_]{2,60}$/', $key)) { http_response_code(422); exit("Bad key\n"); }

$envPath = dirname(__DIR__) . '/.env';
if (!is_writable($envPath)) { http_response_code(500); exit("Cannot write .env\n"); }

$env = file_get_contents($envPath);
$line = $key . '="' . str_replace('"', '\"', $value) . '"';

if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $env)) {
    $env = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $env, 1);
    $action = 'replaced';
} else {
    $env = rtrim($env, "\n") . "\n" . $line . "\n";
    $action = 'added';
}

file_put_contents($envPath, $env);

echo "{$key} {$action} ({$value} chars hidden: " . strlen($value) . ")\n";

// The config cache holds the old value until it is rebuilt.
try {
    require dirname(__DIR__) . '/vendor/autoload.php';
    $app = require_once dirname(__DIR__) . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $kernel->call('config:cache');
    echo "config:cache rebuilt\n";
} catch (Throwable $e) {
    echo "Could not rebuild the config cache: " . $e->getMessage() . "\n";
}

@file_put_contents(__FILE__, "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain');\necho \"Gone.\\n\";\n");
echo "Setter neutralised.\n";
