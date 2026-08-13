<?php
/**
 * wht-log-v1.php — temporary: show the tail of the Laravel log. Secret-gated.
 * Delete once the WHT rates page is fixed.
 */
if (($_GET['secret'] ?? '') !== 'fti2026deploy') {
    http_response_code(404);
    exit('Not Found');
}

$log = dirname(__DIR__) . '/storage/logs/laravel.log';

if (!is_readable($log)) {
    exit("No log at $log\n");
}

header('Content-Type: text/plain; charset=utf-8');

$lines = file($log);
$tail = array_slice($lines, -(int) ($_GET['n'] ?? 120));

echo "=== last " . count($tail) . " lines of laravel.log ===\n\n";
echo implode('', $tail);
