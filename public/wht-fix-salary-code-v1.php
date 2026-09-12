<?php
/**
 * wht-fix-salary-code-v1.php — one-shot: move salary entries onto the section
 * carrying FBR code 64020003 (Salary of Corporate Sector Employees).
 *
 * Entries sat on section "149", which resolves to 14901 — a code inherited from
 * the old portal that does not exist in FBR's statement Codes list, so IRIS
 * rejected every salary row.
 *
 * Overwrites itself with a tombstone rather than unlinking: opcache on this host
 * keeps serving the bytecode of a deleted file.
 */
if (($_GET['secret'] ?? '') !== 'fti2026deploy') {
    http_response_code(404);
    exit('Not Found');
}

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}

$db = new PDO("mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$dry = isset($_GET['dry']);
$TARGET_CODE = '64020003';

// The section carrying the wanted code.
$target = $db->prepare("SELECT section, code, payment_section FROM wht_sections WHERE code = ? LIMIT 1");
$target->execute([$TARGET_CODE]);
$target = $target->fetch();

if (!$target) {
    exit("No section carries code {$TARGET_CODE}. Add it under Settings → FBR Sections first.\n");
}

printf("Target: section %s → code %s (%s)\n\n", $target['section'], $target['code'], $target['payment_section']);

// What is on what today.
echo "Salary entries by section, before:\n";
$before = $db->query("SELECT s.section, COUNT(*) n, ROUND(SUM(s.tax_deducted)) tax,
                             (SELECT code FROM wht_sections x WHERE x.section = s.section LIMIT 1) code
                      FROM wht_salaries s GROUP BY s.section ORDER BY s.section")->fetchAll();

foreach ($before as $r) {
    printf("  %-10s %3d entries  tax %12s  code %s\n",
        $r['section'] ?: '(blank)', $r['n'], number_format((float) $r['tax']),
        $r['code'] ?: 'NONE');
}

// Every salary line for this employer files under the same code.
$move = $db->query("SELECT COUNT(*) FROM wht_salaries WHERE section IS NULL OR section != '" . $target['section'] . "'")->fetchColumn();
printf("\n  %d entries would move to %s (all salaries not already on it)\n", $move, $target['section']);

if ($dry) {
    exit("\nDRY RUN — nothing changed.\n");
}

$db->beginTransaction();
$upd = $db->prepare("UPDATE wht_salaries SET section = ?, updated_at = NOW()
                     WHERE section IS NULL OR section != ?");
$upd->execute([$target['section'], $target['section']]);
$changed = $upd->rowCount();
$db->commit();

printf("\nMoved %d entries to section %s.\n\n", $changed, $target['section']);

echo "Salary entries by section, after:\n";
foreach ($db->query("SELECT s.section, COUNT(*) n, ROUND(SUM(s.tax_deducted)) tax,
                            (SELECT code FROM wht_sections x WHERE x.section = s.section LIMIT 1) code
                     FROM wht_salaries s GROUP BY s.section ORDER BY s.section") as $r) {
    printf("  %-10s %3d entries  tax %12s  code %s\n",
        $r['section'] ?: '(blank)', $r['n'], number_format((float) $r['tax']),
        $r['code'] ?: 'NONE');
}

@file_put_contents(__FILE__, "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain');\necho \"Gone. This one-shot script has already run.\\n\";\n");
echo "\nScript neutralised.\n";
