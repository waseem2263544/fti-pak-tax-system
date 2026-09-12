<?php
/** One-shot: dump a raw Graph SEARCH result for a folder. Neutralises itself. */
if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(404); exit('Not Found'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$sp = app(\App\Services\Microsoft\SharePointClient::class);
$q = $_GET['q'] ?? 'associates';

try {
    $results = $sp->search($q, $sp->rootFolder());
    printf("search '%s' returned %d results\n\n", $q, count($results));

    $folders = array_values(array_filter($results, fn($r) => isset($r['folder'])));
    printf("of which folders: %d\n\n", count($folders));

    if ($folders) {
        echo "── first folder result, raw ──\n";
        echo json_encode($folders[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    }

    $withCount = array_filter($folders, fn($r) => isset($r['folder']['childCount']));
    printf("folders carrying folder.childCount: %d / %d\n", count($withCount), count($folders));
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

@file_put_contents(__FILE__, "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain');\necho \"Gone.\\n\";\n");
echo "\nProbe neutralised.\n";
