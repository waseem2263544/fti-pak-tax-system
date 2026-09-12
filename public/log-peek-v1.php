<?php
/** One-shot: tail the Laravel log. Secret-gated, neutralises itself. */
if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(404); exit('Not Found'); }

header('Content-Type: text/plain; charset=utf-8');

$log = dirname(__DIR__) . '/storage/logs/laravel.log';

if (!is_readable($log)) {
    exit("No log at {$log}\n");
}

printf("log size: %s MB, modified %s\n\n", number_format(filesize($log) / 1048576, 1), date('Y-m-d H:i', filemtime($log)));

$lines = file($log);
$errors = array_values(array_filter($lines, fn($l) => str_contains($l, 'production.ERROR') || str_contains($l, 'production.CRITICAL')));

printf("total ERROR/CRITICAL lines: %d\n\n", count($errors));

echo "── most recent 12 ──\n";
foreach (array_slice($errors, -12) as $e) {
    // First line only; the stack traces are enormous.
    echo '  ' . mb_substr(trim(explode(' {"', $e)[0]), 0, 260) . "\n";
}

echo "\n── grouped by message ──\n";
$groups = [];
foreach ($errors as $e) {
    $msg = trim(explode(' {"', $e)[0]);
    $msg = preg_replace('/^\[[^\]]+\]\s*production\.(ERROR|CRITICAL):\s*/', '', $msg);
    $key = mb_substr($msg, 0, 110);
    $groups[$key] = ($groups[$key] ?? 0) + 1;
}
arsort($groups);
foreach (array_slice($groups, 0, 12, true) as $msg => $n) {
    printf("  %4d x  %s\n", $n, $msg);
}

echo "\n── php-fpm / fatal hints ──\n";
$fatals = array_filter($lines, fn($l) => stripos($l, 'Allowed memory size') !== false
    || stripos($l, 'Maximum execution time') !== false
    || stripos($l, 'Fatal error') !== false);
printf("  memory/time/fatal lines: %d\n", count($fatals));
foreach (array_slice(array_values($fatals), -5) as $f) {
    echo '  ' . mb_substr(trim($f), 0, 220) . "\n";
}

@file_put_contents(__FILE__, "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain');\necho \"Gone.\\n\";\n");
echo "\nPeek neutralised.\n";
