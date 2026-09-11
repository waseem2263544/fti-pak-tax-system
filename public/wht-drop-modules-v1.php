<?php
/**
 * wht-drop-modules-v1.php — one-shot: drop the accounting and tax-news tables.
 *
 * The code for both modules has already been removed. This removes their data.
 * Secret-gated, reports row counts before dropping so the log shows what went,
 * and deletes itself afterwards because the deploy process does not prune.
 *
 * DESTRUCTIVE. Only a database backup can undo it.
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

$db = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Children before parents; foreign key checks are disabled anyway, but the
// order keeps the output readable.
$tables = [
    'acc_journal_entry_lines',
    'acc_journal_entries',
    'acc_sales_invoice_items',
    'acc_sales_invoices',
    'acc_purchase_invoice_items',
    'acc_purchase_invoices',
    'acc_voucher_items',
    'acc_vouchers',
    'acc_recurring_invoices',
    'acc_audit_logs',
    'acc_contacts',
    'acc_accounts',
    'acc_fiscal_years',
    'acc_settings',
    'news_articles',
];

echo "=== Accounting + Tax News: dropping tables ===\n\n";

$dry = isset($_GET['dry']);
if ($dry) echo "DRY RUN — nothing will be dropped.\n\n";

$existing = [];
$total = 0;

foreach ($tables as $t) {
    $q = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    $q->execute([$env['DB_DATABASE'], $t]);

    if (!$q->fetchColumn()) {
        printf("  %-28s not present\n", $t);
        continue;
    }

    $rows = (int) $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $existing[$t] = $rows;
    $total += $rows;
    printf("  %-28s %s rows\n", $t, number_format($rows));
}

printf("\n%d tables, %s rows in total.\n\n", count($existing), number_format($total));

if ($dry) {
    exit("Nothing dropped. Remove ?dry=1 to proceed.\n");
}

$db->exec('SET FOREIGN_KEY_CHECKS = 0');

foreach (array_keys($existing) as $t) {
    $db->exec("DROP TABLE IF EXISTS `$t`");
    printf("  dropped %s\n", $t);
}

$db->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\nVerifying...\n";
$left = 0;
foreach (array_keys($existing) as $t) {
    $q = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    $q->execute([$env['DB_DATABASE'], $t]);
    if ($q->fetchColumn()) { printf("  STILL PRESENT: %s\n", $t); $left++; }
}
echo $left ? "\n$left table(s) survived — check manually.\n" : "  all gone.\n";

echo "\nRemaining tables in the database:\n";
foreach ($db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) {
    echo "  $t\n";
}

printf("\n%s\n", @unlink(__FILE__) ? 'This script has deleted itself.' : 'Delete this script by hand.');
