<?php
/**
 * wht-cleanup-v4.php — remove the temporary log viewer, then itself.
 * The deploy process does not prune files deleted from the repository.
 */
echo "<pre>";

foreach (['wht-log-v1.php'] as $name) {
    $path = __DIR__ . '/' . $name;
    printf("  %-24s %s\n", $name, !file_exists($path) ? 'not present' : (@unlink($path) ? 'deleted' : 'FAILED'));
}

printf("  %-24s %s\n", basename(__FILE__), @unlink(__FILE__) ? 'deleted (self)' : 'FAILED');

echo "</pre>";
