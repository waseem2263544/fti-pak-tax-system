<?php
/**
 * Diagnostic + fix-up for the mPDF / FPDI install on the server.
 *
 * 1. Reports whether the vendor folders exist.
 * 2. Verifies the composer autoload files know about Mpdf and setasign.
 * 3. Tries to require autoload.php and instantiate the class.
 *
 * Once everything reports OK, delete this file.
 */

set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');

$base = realpath(__DIR__ . '/..');
$vendor = $base . '/vendor';

echo "=== mPDF / FPDI install diagnostic ===\n\n";
echo "Project base : $base\n";
echo "Vendor path  : $vendor\n\n";

$checks = [
    'vendor/'                            => $vendor,
    'vendor/autoload.php'                => $vendor . '/autoload.php',
    'vendor/composer/autoload_psr4.php'  => $vendor . '/composer/autoload_psr4.php',
    'vendor/mpdf/mpdf/'                  => $vendor . '/mpdf/mpdf',
    'vendor/mpdf/mpdf/src/Mpdf.php'      => $vendor . '/mpdf/mpdf/src/Mpdf.php',
    'vendor/setasign/fpdi/'              => $vendor . '/setasign/fpdi',
    'vendor/myclabs/'                    => $vendor . '/myclabs',
    'vendor/paragonie/'                  => $vendor . '/paragonie',
];

echo "─── Filesystem checks ─────────────────────\n";
$missing = [];
foreach ($checks as $label => $path) {
    $ok = file_exists($path);
    echo str_pad($label, 40, ' ') . ($ok ? "  OK" : "  MISSING") . "\n";
    if (!$ok) $missing[] = $label;
}

echo "\n─── Autoload PSR-4 entries ────────────────\n";
if (is_file($vendor . '/composer/autoload_psr4.php')) {
    $psr4 = include $vendor . '/composer/autoload_psr4.php';
    foreach (['Mpdf\\', 'setasign\\Fpdi\\', 'Mpdf\\PsrLogAwareTrait\\'] as $ns) {
        echo str_pad($ns, 40, ' ') . (isset($psr4[$ns]) ? "  PRESENT" : "  ABSENT") . "\n";
    }
} else {
    echo "autoload_psr4.php is missing -- vendor/ was not extracted\n";
}

echo "\n─── Try to require autoload + instantiate ─\n";
if (is_file($vendor . '/autoload.php')) {
    try {
        require_once $vendor . '/autoload.php';
        if (class_exists('Mpdf\\Mpdf')) {
            echo "Mpdf\\Mpdf class found.\n";
            try {
                new \Mpdf\Mpdf();
                echo "Mpdf instance created OK.\n";
            } catch (\Throwable $e) {
                echo "Mpdf could be loaded but constructor failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Mpdf\\Mpdf class NOT found after autoload.\n";
        }
    } catch (\Throwable $e) {
        echo "autoload.php failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "autoload.php missing.\n";
}

echo "\n─── PhpSpreadsheet (.xlsx generation) ─────\n";

$ssPath = $vendor . '/phpoffice/phpspreadsheet';
printf("%-42s%s\n", 'vendor/phpoffice/phpspreadsheet/', is_dir($ssPath) ? 'OK' : 'MISSING');

// Everything PhpSpreadsheet needs to read and write .xlsx.
$required = ['zip', 'xmlwriter', 'xmlreader', 'simplexml', 'dom', 'libxml',
             'mbstring', 'iconv', 'gd', 'fileinfo', 'ctype', 'filter', 'zlib'];
$missing = [];

foreach ($required as $ext) {
    $ok = extension_loaded($ext);
    if (!$ok) { $missing[] = $ext; }
    printf("%-42s%s\n", "ext-$ext", $ok ? 'OK' : 'MISSING');
}

if (!$missing && is_dir($ssPath)) {
    try {
        require_once $vendor . '/autoload.php';
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $book->getActiveSheet()->setCellValue('A1', 'test');
        $tmp = sys_get_temp_dir() . '/wht-xlsx-check-' . getmypid() . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($tmp);
        $size = file_exists($tmp) ? filesize($tmp) : 0;
        @unlink($tmp);
        printf("%-42s%s\n", 'write a real .xlsx', $size > 0 ? "OK ($size bytes)" : 'FAILED');
    } catch (Throwable $e) {
        printf("%-42s%s\n", 'write a real .xlsx', 'FAILED: ' . $e->getMessage());
    }
} elseif ($missing) {
    echo "\n  Enable the MISSING extensions in cPanel -> Select PHP Version -> Extensions.\n";
    echo "  Until then the Excel statement, PSID files and spreadsheet import stay unavailable.\n";
}

echo "\n─── Summary ───────────────────────────────\n";
if ($missing) {
    echo "Missing: " . implode(', ', $missing) . "\n";
    echo "\nMost likely cause: deploy.php extracted with the old vendor skip rule.\n";
    echo "Re-run /deploy.php now that the new deploy.php (without the skip) is on disk,\n";
    echo "OR upload the missing vendor folders manually via FTP.\n";
} else {
    echo "Everything looks present. If the controller still throws, the opcache may be stale -- ";
    echo "touch any PHP file or restart PHP-FPM.\n";
}
