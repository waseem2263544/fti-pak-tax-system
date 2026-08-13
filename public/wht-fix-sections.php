<?php
/**
 * wht-fix-sections.php — repair the section list after the initial import.
 *
 * Two faults in migrate-wht.php are corrected here:
 *
 *  1. `code` was UNIQUE. Real FBR data reuses one payment code across several
 *     section labels (64060156 serves both 153(1)(b)/26 and /30), so the
 *     constraint is wrong. It becomes a plain index.
 *
 *  2. The generic starter sections were seeded with codes 64060001-64060049,
 *     which are real FBR codes belonging to *other* sections. They squatted on
 *     those codes, so the import's INSERT IGNORE silently dropped the genuine
 *     rows — including 153(1)(a)/6, /9 and /31, which carry most of the
 *     recorded payments. The seeded rows are removed and the real sections
 *     re-imported.
 *
 * Add ?dry=1 to preview. Delete this file after use.
 */
set_time_limit(300);
echo "<pre><h2>WHT Section Repair</h2>\n";
$dry = isset($_GET['dry']);
if ($dry) echo "DRY RUN — nothing will be written.\n\n";

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$src = new PDO("mysql:host=localhost;dbname=fairtax1_wht;charset=utf8mb4", 'fairtax1_wht', '47yTehPqSv63hSUVAnLn', $opts);
$dst = new PDO("mysql:host=localhost;dbname={$env['DB_DATABASE']};charset=utf8mb4", $env['DB_USERNAME'], $env['DB_PASSWORD'], $opts);
$now = date('Y-m-d H:i:s');

echo "Before: " . $dst->query("SELECT COUNT(*) FROM wht_sections")->fetchColumn() . " sections\n\n";

// ── Section identifiers that must survive ──
$used = [];
foreach (['SELECT DISTINCT section FROM wht_purchases WHERE section IS NOT NULL',
          'SELECT DISTINCT section FROM wht_salaries WHERE section IS NOT NULL',
          'SELECT DISTINCT section FROM wht_tax_rates',
          'SELECT DISTINCT default_section FROM wht_parties WHERE default_section IS NOT NULL'] as $q) {
    foreach ($dst->query($q)->fetchAll(PDO::FETCH_COLUMN) as $s) {
        if ($s !== '') $used[$s] = true;
    }
}
echo "Section identifiers in use: " . count($used) . "\n\n";

// ── 1. Relax the unique constraint on `code` ──
echo "── Index ──\n";
$hasUnique = $dst->query("SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'wht_sections'
      AND index_name = 'uk_wht_section_code'")->fetchColumn();

if ($hasUnique) {
    if (!$dry) {
        $dst->exec("ALTER TABLE wht_sections DROP INDEX uk_wht_section_code");
        $dst->exec("ALTER TABLE wht_sections ADD INDEX idx_wht_section_code (code)");
    }
    echo "  unique key on `code` replaced with a plain index\n";
} else {
    echo "  already a plain index\n";
}
echo "\n";

// ── 2. Remove the generic seeded rows ──
echo "── Seeded starter rows ──\n";
$seededCodes = array_map(fn($i) => sprintf('640600%02d', $i), range(1, 49));
$seededCodes = array_merge($seededCodes, ['14901', '14902', '14903']);
$in = implode(',', array_fill(0, count($seededCodes), '?'));

// A seeded row is only removable if nothing references its section label.
$stmt = $dst->prepare("SELECT id, section, code FROM wht_sections WHERE code IN ($in)");
$stmt->execute($seededCodes);

$removable = [];
$protected = [];
foreach ($stmt->fetchAll() as $row) {
    if (isset($used[$row['section']])) {
        $protected[] = $row;
    } else {
        $removable[] = $row;
    }
}

echo "  found      : " . (count($removable) + count($protected)) . "\n";
echo "  removing   : " . count($removable) . "\n";
echo "  protected  : " . count($protected) . " (referenced by real data)\n";
foreach ($protected as $r) {
    printf("    keep %-14s %s\n", $r['section'], $r['code']);
}

if (!$dry && $removable) {
    $dst->exec("DELETE FROM wht_sections WHERE id IN (" . implode(',', array_column($removable, 'id')) . ")");
}
echo "\n";

// ── 3. Re-import the real sections, now that the codes are free ──
echo "── Re-import from the portal ──\n";
$rows = $src->query("SELECT * FROM tax_payment_sections ORDER BY company_id, code")->fetchAll();

$seen = [];
$added = 0;
$skipped = 0;

$exists = $dst->prepare("SELECT COUNT(*) FROM wht_sections WHERE section = ? AND code = ? AND payment_section = ?");
$ins = $dst->prepare("INSERT INTO wht_sections
    (section, payment_nature, payment_section, code, applies_to, is_active, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, 1, ?, ?)");

foreach ($rows as $r) {
    // The source itself repeats rows; dedupe on the full identity.
    $key = $r['section'] . '|' . $r['code'] . '|' . $r['payment_section'];
    if (isset($seen[$key])) { $skipped++; continue; }
    $seen[$key] = true;

    $applies = str_starts_with((string) $r['section'], '149') ? 'salary' : 'purchase';

    if ($dry) { $added++; continue; }

    $exists->execute([$r['section'], $r['code'], $r['payment_section']]);
    if ($exists->fetchColumn()) { $skipped++; continue; }

    $ins->execute([$r['section'], $r['payment_nature'], $r['payment_section'],
                   $r['code'], $applies, $now, $now]);
    $added++;
}
echo "  source rows: " . count($rows) . "\n";
echo "  added      : $added\n";
echo "  duplicates : $skipped\n\n";

// ── 4. Verify every referenced section now resolves ──
echo "── Verification ──\n";
$missing = [];
foreach (array_keys($used) as $s) {
    $c = $dst->prepare("SELECT COUNT(*) FROM wht_sections WHERE section = ? AND is_active = 1");
    $c->execute([$s]);
    if (!$c->fetchColumn()) $missing[] = $s;
}

if ($missing) {
    echo "  STILL MISSING:\n";
    foreach ($missing as $s) echo "    ! $s\n";
} else {
    echo "  OK — every section used by a rate, party or transaction resolves.\n";
}

// Confirm the rate matrix can actually be reached from the entry form.
echo "\n  Rate matrix reachability:\n";
$q = $dst->query("SELECT r.section, r.category, r.atl_status, r.rate,
                         (SELECT COUNT(*) FROM wht_sections s WHERE s.section = r.section AND s.is_active = 1) AS listed
                  FROM wht_tax_rates r ORDER BY r.section");
foreach ($q as $r) {
    printf("    %-16s %-12s %-10s %6s%%   %s\n", $r['section'], $r['category'], $r['atl_status'],
        rtrim(rtrim($r['rate'], '0'), '.'), $r['listed'] ? 'selectable' : 'NOT IN DROPDOWN');
}

$total = $dst->query("SELECT COUNT(*) FROM wht_sections")->fetchColumn();
$active = $dst->query("SELECT COUNT(*) FROM wht_sections WHERE is_active = 1")->fetchColumn();
echo "\n" . str_repeat('=', 56) . "\n";
echo $dry ? "DRY RUN COMPLETE\n" : "REPAIR COMPLETE\n";
echo "Sections: $active active of $total\n";
echo "</pre>";
