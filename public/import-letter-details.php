<?php
/**
 * Load the letter register from database/data/letter-numbers.csv.
 *
 * Re-runnable: rows key on (reference, name), because the register reuses 34
 * references across different matters and neither field alone identifies a row.
 */
echo "<pre>";
if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(403); exit("Forbidden\n"); }

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$csv = dirname(__DIR__) . '/database/data/letter-numbers.csv';
if (!is_readable($csv)) { exit("Missing {$csv}\n"); }

$normalise = function (string $s): string {
    $s = mb_strtolower($s);
    $s = preg_replace('/\b(m\/s|mr|mrs|ms|dr|messrs|the)\b\.?/u', ' ', $s);
    $s = preg_replace('/\b(pvt|private|ltd|limited|co|company|inc)\b\.?/u', ' ', $s);
    $s = preg_replace('/[^a-z0-9]+/u', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
};

$clients = [];
foreach ($pdo->query("SELECT id, name FROM clients") as $c) {
    $k = $normalise($c['name']);
    if ($k !== '' && !isset($clients[$k])) { $clients[$k] = $c['id']; }
}
echo "Clients on file: " . count($clients) . "\n\n";

$find = $pdo->prepare("SELECT id FROM letter_numbers WHERE reference = ? AND COALESCE(client_name,'') = ? LIMIT 1");
$ins  = $pdo->prepare("INSERT INTO letter_numbers
    (`date`, raw_date, reference, sequence_no, `year`, client_id, client_name, description, source, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ref-no-sheet', NOW(), NOW())");
$upd  = $pdo->prepare("UPDATE letter_numbers
    SET `date` = ?, raw_date = ?, sequence_no = ?, `year` = ?, client_id = ?, description = ?,
        source = 'ref-no-sheet', updated_at = NOW() WHERE id = ?");

$fh = fopen($csv, 'r');
$cols = array_flip(fgetcsv($fh));
$added = $updated = $linked = $rows = 0;

$pdo->beginTransaction();
while (($row = fgetcsv($fh)) !== false) {
    if (count($row) < 7) { continue; }
    $g = fn($k) => trim($row[$cols[$k]] ?? '');

    $reference = $g('reference');
    if ($reference === '') { continue; }
    $rows++;

    $name     = $g('name');
    $clientId = $name !== '' ? ($clients[$normalise($name)] ?? null) : null;
    if ($clientId) { $linked++; }

    $date = $g('date') ?: null;
    $raw  = $g('raw_date') ?: null;
    $seq  = $g('sequence_no') !== '' ? (int) $g('sequence_no') : null;
    $year = $g('year') !== '' ? (int) $g('year') : null;
    $desc = $g('description') ?: null;

    $find->execute([$reference, $name]);
    if ($id = $find->fetchColumn()) {
        $upd->execute([$date, $raw, $seq, $year, $clientId, $desc, $id]);
        $updated++;
    } else {
        $ins->execute([$date, $raw, $reference, $seq, $year, $clientId, $name ?: null, $desc]);
        $added++;
    }
}
$pdo->commit();
fclose($fh);

printf("Rows in CSV : %d\nInserted    : %d\nUpdated     : %d\nLinked to a client record: %d (%d left as plain names)\n",
    $rows, $added, $updated, $linked, $rows - $linked);
printf("\nletter_numbers now holds %d rows.\n", $pdo->query("SELECT COUNT(*) FROM letter_numbers")->fetchColumn());
echo "\nDone!\n</pre>";
