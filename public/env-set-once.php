<?php
/**
 * Set a handful of .env values on the server, once.
 *
 * Values arrive in the POST body rather than the query string, so they do
 * not end up in an access log, and only the keys below may be written. The
 * script replaces itself with a tombstone as soon as it has run: a file that
 * edits the environment has no business staying reachable, and deleting it
 * is not enough while opcache still holds the compiled copy.
 */
if (($_GET['secret'] ?? '') !== 'fti2026deploy') {
    http_response_code(403);
    die('Forbidden');
}

$allowed = ['SITE_MEDIA_BASE', 'VERCEL_REVALIDATE_URL', 'VERCEL_REVALIDATE_SECRET', 'BLOG_FEED_URL'];
$base = dirname(__DIR__);
$envFile = $base . '/.env';

header('Content-Type: text/plain');

$env = file_get_contents($envFile);
if ($env === false) {
    http_response_code(500);
    die("Cannot read .env\n");
}

$written = [];

foreach ($allowed as $key) {
    if (!isset($_POST[$key])) {
        continue;
    }

    $value = trim((string) $_POST[$key]);
    $line = $key . '=' . (preg_match('/[\s#"\']/', $value) ? '"' . $value . '"' : $value);

    if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $env)) {
        $env = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $env);
    } else {
        $env = rtrim($env) . "\n" . $line . "\n";
    }

    $written[] = $key;
}

if (!$written) {
    die("Nothing to write. Send the values in the POST body.\n");
}

file_put_contents($envFile, $env);
echo "Wrote: " . implode(', ', $written) . "\n";

// The config is cached on this server, so a new value is invisible until the
// cache is rebuilt.
try {
    require $base . '/vendor/autoload.php';
    $app = require_once $base . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $kernel->call('config:cache');
    echo "Config cache rebuilt.\n";
} catch (Throwable $e) {
    @unlink($base . '/bootstrap/cache/config.php');
    echo "Config cache cleared (rebuild failed: " . $e->getMessage() . ").\n";
}

file_put_contents(__FILE__, "<?php http_response_code(410); die('Gone');\n");
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}
echo "This script has retired itself.\n";
