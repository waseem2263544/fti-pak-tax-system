<?php
/**
 * wht-purge-v1.php — one-shot: delete the Oct–Dec 2025 vendor payments for one
 * withholding agent, at the user's explicit instruction.
 *
 * Secret-gated, dry-run capable, reports exactly what it removed, and deletes
 * itself. DESTRUCTIVE — 21 of these carry a CPR, meaning tax already deposited.
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

$agent = $db->query("SELECT id, name FROM wht_companies WHERE name LIKE '%Universal%' LIMIT 1")->fetch();

if (!$agent) {
    exit("Agent not found.\n");
}

echo "Agent: {$agent['name']}\n";
echo "Scope: vendor payments with a tax period of Oct, Nov or Dec 2025\n\n";

$sql = "SELECT p.id, p.period_month, p.section, p.gross_amount, p.tax_rate, p.tax_withheld,
               p.psid_no, p.cpr_no, t.name AS payee
        FROM wht_purchases p
        JOIN wht_parties t ON t.id = p.party_id
        WHERE p.wht_company_id = ?
          AND p.period_month >= '2025-10-01' AND p.period_month <= '2025-12-01'
        ORDER BY p.period_month, t.name";

$stmt = $db->prepare($sql);
$stmt->execute([$agent['id']]);
$rows = $stmt->fetchAll();

$tax = 0; $deposited = 0; $depositedTax = 0;

foreach ($rows as $r) {
    $tax += (float) $r['tax_withheld'];
    if (trim((string) $r['cpr_no']) !== '') { $deposited++; $depositedTax += (float) $r['tax_withheld']; }
    printf("  %-10s %-30s %-14s %14s  tax %10s  %s\n",
        substr($r['period_month'], 0, 7), mb_substr($r['payee'], 0, 30), $r['section'],
        number_format((float) $r['gross_amount'], 0), number_format((float) $r['tax_withheld'], 0),
        trim((string) $r['cpr_no']) !== '' ? $r['cpr_no'] : '—');
}

printf("\n  %d entries · tax %s · %d with a CPR (%s already deposited)\n",
    count($rows), number_format($tax, 0), $deposited, number_format($depositedTax, 0));

if ($dry) {
    exit("\nDRY RUN — nothing deleted. Remove ?dry=1 to proceed.\n");
}

if (!$rows) {
    exit("\nNothing to delete.\n");
}

$ids = array_column($rows, 'id');
$in = implode(',', array_fill(0, count($ids), '?'));

$db->beginTransaction();
$del = $db->prepare("DELETE FROM wht_purchases WHERE id IN ($in)");
$del->execute($ids);
$count = $del->rowCount();
$db->commit();

printf("\nDeleted %d entries.\n", $count);

$stmt->execute([$agent['id']]);
printf("Remaining in Oct–Dec 2025: %d\n", count($stmt->fetchAll()));

printf("\n%s\n", @unlink(__FILE__) ? 'This script has deleted itself.' : 'Delete this script by hand.');
