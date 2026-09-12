<?php
/** One-shot: dump the raw Graph response for the Documents root folder. Neutralises itself. */
if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(404); exit('Not Found'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$sp = app(\App\Services\Microsoft\SharePointClient::class);

try {
    $root = $sp->rootFolder();
    echo "root folder id : {$root}\n";
    echo "drive id       : " . $sp->driveId() . "\n\n";

    $children = $sp->children($root);
    printf("children returned: %d\n\n", count($children));

    foreach (array_slice($children, 0, 3) as $i => $c) {
        echo "── item " . ($i + 1) . " ─────────────────────────\n";
        echo json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    }

    echo "── field presence across all items ──\n";
    $fields = ['name', 'folder', 'file', 'size', 'webUrl', 'parentReference', 'lastModifiedBy'];
    foreach ($fields as $f) {
        $n = count(array_filter($children, fn($c) => array_key_exists($f, $c)));
        printf("  %-18s %d / %d\n", $f, $n, count($children));
    }

    $withCount = array_filter($children, fn($c) => isset($c['folder']['childCount']));
    printf("  %-18s %d / %d\n", 'folder.childCount', count($withCount), count($children));
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

@file_put_contents(__FILE__, "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain');\necho \"Gone.\\n\";\n");
echo "\nProbe neutralised.\n";
