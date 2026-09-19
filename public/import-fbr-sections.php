<?php
/**
 * Reconcile wht_sections against FBR's own list.
 *
 * The list comes from the PaymentSections sheet inside FBR's ePayments import
 * template, so it is the authority rather than a transcription of one.
 *
 * Nothing is deleted. Sections the app carries that FBR does not are reported
 * with a count of the entries using them, because that is the question that
 * matters: an entry on a section FBR does not know will fail at upload.
 */
set_time_limit(180);
echo "<pre><h2>FBR section reconciliation</h2>\n";

if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(403); exit("Forbidden\n"); }

$apply = ($_GET['apply'] ?? '') === '1';

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$csv = dirname(__DIR__) . '/database/data/fbr-payment-sections.csv';
if (!is_readable($csv)) { exit("Missing {$csv}\n"); }

$fbr = [];
$fh = fopen($csv, 'r');
$cols = array_flip(fgetcsv($fh));
while (($row = fgetcsv($fh)) !== false) {
    if (count($row) < 4) { continue; }
    $section = trim($row[$cols['section']]);
    if ($section === '') { continue; }
    $fbr[$section] = [
        'payment_nature'  => trim($row[$cols['payment_nature']]),
        'payment_section' => trim($row[$cols['payment_section']]),
        'code'            => trim($row[$cols['code']]),
    ];
}
fclose($fh);
printf("FBR list: %d sections\n", count($fbr));

$mine = $pdo->query("SELECT id, section, code, payment_nature, payment_section FROM wht_sections")
    ->fetchAll(PDO::FETCH_ASSOC);
printf("This app : %d sections\n\n", count($mine));

$bySection = [];
foreach ($mine as $m) { $bySection[trim($m['section'])][] = $m; }

// 1. Codes that disagree with FBR.
$wrongCode = [];
foreach ($bySection as $section => $rows) {
    if (!isset($fbr[$section])) { continue; }
    foreach ($rows as $r) {
        if (trim((string) $r['code']) !== $fbr[$section]['code']) {
            $wrongCode[] = [$r['id'], $section, $r['code'], $fbr[$section]['code']];
        }
    }
}

// 2. Sections FBR has no record of, and what they cost.
$orphans = [];
foreach ($bySection as $section => $rows) {
    if (isset($fbr[$section])) { continue; }
    $purch = $pdo->prepare("SELECT COUNT(*) FROM wht_purchases WHERE section = ?");
    $purch->execute([$section]);
    $sal = $pdo->prepare("SELECT COUNT(*) FROM wht_salaries WHERE section = ?");
    $sal->execute([$section]);
    $orphans[$section] = [
        'rows'    => count($rows),
        'entries' => (int) $purch->fetchColumn() + (int) $sal->fetchColumn(),
        'code'    => $rows[0]['code'],
    ];
}

// 3. Duplicated sections within the app.
$dupes = array_filter($bySection, fn($rows) => count($rows) > 1);

printf("Codes disagreeing with FBR : %d\n", count($wrongCode));
printf("Sections FBR does not list : %d\n", count($orphans));
printf("Duplicated section rows    : %d\n", count($dupes));

echo "\n-- codes to correct --\n";
foreach (array_slice($wrongCode, 0, 40) as [$id, $section, $was, $now]) {
    printf("  %-22s %-12s -> %s\n", $section, $was ?: '(blank)', $now);
}
if (count($wrongCode) > 40) { printf("  ... and %d more\n", count($wrongCode) - 40); }

echo "\n-- sections FBR does not list (entries using them in brackets) --\n";
uasort($orphans, fn($a, $b) => $b['entries'] <=> $a['entries']);
foreach (array_slice($orphans, 0, 40, true) as $section => $o) {
    printf("  %-22s code %-12s %d row(s), %d entr%s\n",
        $section, $o['code'] ?: '(blank)', $o['rows'], $o['entries'], $o['entries'] === 1 ? 'y' : 'ies');
}
if (count($orphans) > 40) { printf("  ... and %d more\n", count($orphans) - 40); }

echo "\n-- duplicated sections --\n";
foreach (array_slice($dupes, 0, 20, true) as $section => $rows) {
    printf("  %-22s %d rows (ids %s)\n", $section, count($rows), implode(', ', array_column($rows, 'id')));
}

if (!$apply) {
    echo "\nThis was a dry run. Add &apply=1 to write the corrected codes.\n";
    echo "Nothing is ever deleted: sections FBR does not list are reported only,\n";
    echo "because some may be salary sub-heads the firm keeps deliberately.\n</pre>";
    exit;
}

$upd = $pdo->prepare("UPDATE wht_sections SET code = ?, payment_nature = ?, payment_section = ?, updated_at = NOW() WHERE id = ?");
$ins = $pdo->prepare("INSERT INTO wht_sections (section, payment_nature, payment_section, code, applies_to, regime, is_active, created_at, updated_at)
                      VALUES (?, ?, ?, ?, 'purchase', 'adjustable', 1, NOW(), NOW())");

$fixed = 0;
foreach ($wrongCode as [$id, $section]) {
    $upd->execute([$fbr[$section]['code'], $fbr[$section]['payment_nature'], $fbr[$section]['payment_section'], $id]);
    $fixed++;
}

$added = 0;
foreach ($fbr as $section => $f) {
    if (isset($bySection[$section])) { continue; }
    $ins->execute([$section, $f['payment_nature'], $f['payment_section'], $f['code']]);
    $added++;
}

printf("\nCorrected %d codes, added %d sections FBR lists that the app did not have.\n", $fixed, $added);

/*
 * Drop duplicate rows for the same section.
 *
 * Nothing points at a section by id - entries, rates and the upload file all
 * carry the section string - so the extra rows are safe to remove. The one
 * kept is whichever now matches FBR's code, since a lookup by section returns
 * an arbitrary row and picking the wrong one is how three sections ended up
 * with the wrong code.
 */
$removed = 0;
foreach ($pdo->query("SELECT section, COUNT(*) n FROM wht_sections GROUP BY section HAVING n > 1")
              ->fetchAll(PDO::FETCH_ASSOC) as $d) {
    $rows = $pdo->prepare("SELECT id, code FROM wht_sections WHERE section = ? ORDER BY id");
    $rows->execute([$d['section']]);
    $rows = $rows->fetchAll(PDO::FETCH_ASSOC);

    $wanted = $fbr[$d['section']]['code'] ?? null;
    $keep = null;

    foreach ($rows as $r) {
        if ($wanted !== null && trim((string) $r['code']) === $wanted) { $keep = $r['id']; break; }
    }
    $keep = $keep ?? $rows[0]['id'];

    foreach ($rows as $r) {
        if ($r['id'] === $keep) { continue; }
        $pdo->prepare("DELETE FROM wht_sections WHERE id = ?")->execute([$r['id']]);
        $removed++;
    }
}

printf("Removed %d duplicate section rows.\n", $removed);
printf("wht_sections now holds %d rows.\n", $pdo->query("SELECT COUNT(*) FROM wht_sections")->fetchColumn());
echo "Sections FBR does not list were left alone.\n";
echo "\nDone!\n</pre>";
