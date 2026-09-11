<?php
/**
 * wht-cleanup-v5.php — remove the server-side diagnostic, then itself.
 * The deploy process copies files in but does not prune ones deleted from git.
 */
echo "<pre>";

foreach (['check-mpdf.php'] as $name) {
    $path = __DIR__ . '/' . $name;
    printf("  %-24s %s\n", $name, !file_exists($path) ? 'not present' : (@unlink($path) ? 'deleted' : 'FAILED — remove by hand'));
}

printf("  %-24s %s\n", basename(__FILE__), @unlink(__FILE__) ? 'deleted (self)' : 'FAILED — remove by hand');

echo "\nDone.\n</pre>";
