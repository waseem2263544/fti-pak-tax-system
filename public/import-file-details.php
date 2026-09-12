<?php
/**
 * Load the file register from database/data/files-details.csv.
 *
 * Re-runnable: rows are matched on (file_no, client_name), so running it again
 * after dropping a fuller CSV in place fills in what is missing and updates
 * descriptions rather than duplicating anything. File numbers are deliberately
 * not unique - the register itself reuses one.
 */
echo "<pre>";
if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(403); exit("Forbidden\n"); }

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$csv = dirname(__DIR__) . '/database/data/files-details.csv';
if (!is_readable($csv)) { exit("Missing {$csv}\n"); }

// Client names are matched loosely: the register writes "Mr. Ghafoor ur Rehman"
// where the client list says "Ghafoor ur Rehman".
$normalise = function (string $s): string {
    $s = mb_strtolower($s);
    $s = preg_replace('/\b(m\/s|mr|mrs|ms|dr|messrs|the)\b\.?/u', ' ', $s);
    $s = preg_replace('/\b(pvt|private|ltd|limited|co|company|inc)\b\.?/u', ' ', $s);
    $s = preg_replace('/[^a-z0-9]+/u', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
};

$clients = [];
foreach ($pdo->query("SELECT id, name FROM clients") as $c) {
    $key = $normalise($c['name']);
    if ($key !== '' && !isset($clients[$key])) { $clients[$key] = $c['id']; }
}
echo "Clients on file: " . count($clients) . "\n\n";

$find = $pdo->prepare("SELECT id FROM file_numbers WHERE file_no = ? AND COALESCE(client_name,'') = ? LIMIT 1");
$ins  = $pdo->prepare("INSERT INTO file_numbers (file_no, client_id, client_name, description, source, created_at, updated_at)
                       VALUES (?, ?, ?, ?, 'files-details-sheet', NOW(), NOW())");
$upd  = $pdo->prepare("UPDATE file_numbers SET client_id = ?, description = ?, source = 'files-details-sheet', updated_at = NOW() WHERE id = ?");

$fh = fopen($csv, 'r');
$header = fgetcsv($fh);
$cols = array_flip($header);

$added = $updated = $linked = 0; $rows = 0;
$pdo->beginTransaction();

while (($row = fgetcsv($fh)) !== false) {
    if (count($row) < 4) { continue; }
    $fileNo = (int) trim($row[$cols['file_no']]);
    $name   = trim($row[$cols['name']]);
    $desc   = trim($row[$cols['description']]);
    if ($fileNo <= 0 || $name === '') { continue; }
    $rows++;

    $clientId = $clients[$normalise($name)] ?? null;
    if ($clientId) { $linked++; }

    $find->execute([$fileNo, $name]);
    $existing = $find->fetchColumn();

    if ($existing) {
        $upd->execute([$clientId, $desc ?: null, $existing]);
        $updated++;
    } else {
        $ins->execute([$fileNo, $clientId, $name, $desc ?: null]);
        $added++;
    }
}

$pdo->commit();
fclose($fh);

printf("Rows in CSV : %d\nInserted    : %d\nUpdated     : %d\nLinked to a client record: %d (%d left as plain names)\n",
    $rows, $added, $updated, $linked, $rows - $linked);
printf("\nfile_numbers now holds %d rows.\n", $pdo->query("SELECT COUNT(*) FROM file_numbers")->fetchColumn());
echo "\nDone!\n</pre>";
