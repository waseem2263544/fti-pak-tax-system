<?php
/**
 * wht-fix-sections.php — retire the generic sections seeded by migrate-wht.php.
 *
 * The live portal carries the full official FBR payment-code list, whose section
 * identifiers are suffixed (153(1)(a)/9, 233/2, ...) and which the rate matrix
 * and every recorded transaction key on. migrate-wht.php had also seeded a
 * shorter generic list (153(1)(a), 153(1)(b), ... codes 64060001-64060049),
 * which would otherwise sit in the dropdowns resolving to no rate.
 *
 * Seeded rows that nothing references are set is_active = 0 — not deleted, so
 * they can be switched back on from Settings → FBR Sections. Anything the data
 * actually uses is left alone.
 *
 * Add ?dry=1 to preview. Delete this file after use.
 */
echo "<pre><h2>WHT Section Cleanup</h2>\n";
$dry = isset($_GET['dry']);
if ($dry) echo "DRY RUN — nothing will be written.\n\n";

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$db = new PDO("mysql:host=localhost;dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// The exact codes migrate-wht.php seeds.
$seededCodes = array_map(fn($i) => sprintf('640600%02d', $i), range(1, 49));
$seededCodes = array_merge($seededCodes, ['14901', '14902', '14903']);
$in = implode(',', array_fill(0, count($seededCodes), '?'));

// Section strings that are genuinely in use.
$used = [];
foreach (['SELECT DISTINCT section FROM wht_purchases WHERE section IS NOT NULL',
          'SELECT DISTINCT section FROM wht_salaries WHERE section IS NOT NULL',
          'SELECT DISTINCT section FROM wht_tax_rates',
          'SELECT DISTINCT default_section FROM wht_parties WHERE default_section IS NOT NULL'] as $q) {
    foreach ($db->query($q)->fetchAll(PDO::FETCH_COLUMN) as $s) {
        if ($s !== '') $used[$s] = true;
    }
}
echo "Section identifiers in use: " . count($used) . "\n";
foreach (array_slice(array_keys($used), 0, 40) as $s) echo "  · $s\n";
echo "\n";

// Seeded rows, split by whether anything references them.
$stmt = $db->prepare("SELECT id, section, code, payment_nature FROM wht_sections WHERE code IN ($in)");
$stmt->execute($seededCodes);
$seeded = $stmt->fetchAll();

$retire = [];
$keep = [];
foreach ($seeded as $row) {
    if (isset($used[$row['section']])) {
        $keep[] = $row;
    } else {
        $retire[] = $row;
    }
}

echo "Seeded rows found      : " . count($seeded) . "\n";
echo "  kept (referenced)    : " . count($keep) . "\n";
echo "  retiring (unused)    : " . count($retire) . "\n\n";

foreach ($keep as $r) {
    printf("  KEEP    %-14s %-10s %s\n", $r['section'], $r['code'], mb_substr($r['payment_nature'], 0, 40));
}
echo "\n";

if (!$dry && $retire) {
    $ids = implode(',', array_column($retire, 'id'));
    $db->exec("UPDATE wht_sections SET is_active = 0, updated_at = NOW() WHERE id IN ($ids)");
}

// Anything referenced but missing from the section list would break a dropdown.
echo "Referenced but NOT in wht_sections (would show blank in a dropdown):\n";
$missing = 0;
foreach (array_keys($used) as $s) {
    $c = $db->prepare("SELECT COUNT(*) FROM wht_sections WHERE section = ?");
    $c->execute([$s]);
    if (!$c->fetchColumn()) { echo "  ! $s\n"; $missing++; }
}
echo $missing ? "\n" : "  none — every referenced section exists.\n\n";

$active = $db->query("SELECT COUNT(*) FROM wht_sections WHERE is_active = 1")->fetchColumn();
$total  = $db->query("SELECT COUNT(*) FROM wht_sections")->fetchColumn();
echo str_repeat('=', 56) . "\n";
echo $dry ? "DRY RUN COMPLETE\n" : "CLEANUP COMPLETE\n";
echo "Active sections: $active of $total\n";
echo "Retired rows keep their data and can be re-enabled in Settings → FBR Sections.\n";
echo "</pre>";
