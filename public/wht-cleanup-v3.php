<?php
/**
 * wht-cleanup-v3.php — delete the one-shot WHT import and diagnostic scripts
 * from the server.
 *
 * The deploy process copies files in but does not prune ones removed from the
 * repository, so scripts deleted in git stay live. This removes them, then
 * removes itself.
 */
echo "<pre><h2>Removing one-shot WHT scripts</h2>\n";

$targets = [
    'wht-diagnose.php',
    'wht-fix-sections.php',
    'wht-verify.php',
    'wht-rate-timeline.php',
    'wht-challan-files.php',
    'wht-challans-v2.php',
    'migrate-wht-import.php',
];

foreach ($targets as $name) {
    $path = __DIR__ . '/' . $name;

    if (!file_exists($path)) {
        printf("  %-28s not present\n", $name);
        continue;
    }

    printf("  %-28s %s\n", $name, @unlink($path) ? 'deleted' : 'FAILED — delete by hand');
}

// migrate-wht.php is deliberately kept: it is the documented, idempotent setup
// path for this module and reads its credentials from .env.
echo "\n  migrate-wht.php              kept (schema setup, reads .env)\n";

$self = __FILE__;
echo "\n  " . str_pad(basename($self), 28) . (@unlink($self) ? 'deleted (self)' : 'FAILED — delete by hand') . "\n";

echo "\nDone.\n</pre>";
