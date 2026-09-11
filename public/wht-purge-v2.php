<?php
/**
 * wht-purge-v2.php — one-shot: delete the September 2025 salary records.
 *
 * Overwrites itself with a tombstone rather than unlinking: on this host
 * opcache keeps serving the compiled bytecode of a deleted file, which left the
 * previous purge endpoint live after it had "removed" itself.
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

$agent = $db->query("SELECT id, name FROM wht_companies WHERE name LIKE '%Universal%' LIMIT 1")->fetch();
echo "Agent: {$agent['name']}\nScope: salary records with a salary month of Sep 2025\n\n";

$sql = "SELECT s.id, s.taxable_salary, s.exempt_amount, s.total_salary, s.tax_deducted,
               s.final_net_payment, s.cpr_no, t.name AS payee, t.cnic_ntn
        FROM wht_salaries s JOIN wht_parties t ON t.id = s.employee_id
        WHERE s.wht_company_id = ? AND s.salary_month = '2025-09-01'
        ORDER BY t.name";

$stmt = $db->prepare($sql);
$stmt->execute([$agent['id']]);
$rows = $stmt->fetchAll();

$tax = 0; $withCpr = 0;
foreach ($rows as $r) {
    $tax += (float) $r['tax_deducted'];
    if (trim((string) $r['cpr_no']) !== '') $withCpr++;
    printf("  %-24s %-16s total %12s  tax %10s  %s\n",
        mb_substr($r['payee'], 0, 24), $r['cnic_ntn'],
        number_format((float) $r['total_salary'], 0), number_format((float) $r['tax_deducted'], 0),
        trim((string) $r['cpr_no']) !== '' ? $r['cpr_no'] : '—');
}

printf("\n  %d records · tax %s · %d with a CPR\n", count($rows), number_format($tax, 0), $withCpr);

if ($rows) {
    $ids = array_column($rows, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $db->beginTransaction();
    $del = $db->prepare("DELETE FROM wht_salaries WHERE id IN ($in)");
    $del->execute($ids);
    $count = $del->rowCount();
    $db->commit();
    printf("\nDeleted %d records.\n", $count);
}

$stmt->execute([$agent['id']]);
printf("Remaining in Sep 2025: %d\n", count($stmt->fetchAll()));

// Neutralise rather than unlink — see the note at the top.
@file_put_contents(__FILE__, "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain');\necho \"Gone. This one-shot script has already run.\\n\";\n");
echo "\nScript neutralised.\n";
