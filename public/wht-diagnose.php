<?php
/** Temporary read-only diagnostic for the WHT import. Delete after use. */
echo "<pre>";

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$src = new PDO("mysql:host=localhost;dbname=fairtax1_wht;charset=utf8mb4", 'fairtax1_wht', '47yTehPqSv63hSUVAnLn', $opts);
$dst = new PDO("mysql:host=localhost;dbname={$env['DB_DATABASE']};charset=utf8mb4", $env['DB_USERNAME'], $env['DB_PASSWORD'], $opts);

echo "OLD tax_payment_sections\n";
echo "  total rows      : " . $src->query("SELECT COUNT(*) FROM tax_payment_sections")->fetchColumn() . "\n";
echo "  distinct codes  : " . $src->query("SELECT COUNT(DISTINCT code) FROM tax_payment_sections")->fetchColumn() . "\n";
echo "  distinct section: " . $src->query("SELECT COUNT(DISTINCT section) FROM tax_payment_sections")->fetchColumn() . "\n";
echo "  per company     : ";
foreach ($src->query("SELECT company_id, COUNT(*) c FROM tax_payment_sections GROUP BY company_id") as $r) {
    echo "co{$r['company_id']}={$r['c']} ";
}
echo "\n\n  sample of codes NOT matching the standard 6406xxxx/149xx pattern:\n";
$odd = $src->query("SELECT section, payment_nature, payment_section, code FROM tax_payment_sections
                    WHERE code NOT LIKE '6406%' AND code NOT LIKE '149%' LIMIT 25");
foreach ($odd as $r) {
    printf("    %-12s %-28s %-34s %s\n", $r['section'], mb_substr($r['payment_nature'],0,26), mb_substr($r['payment_section'],0,32), $r['code']);
}

echo "\n\nNEW wht_sections\n";
echo "  total rows      : " . $dst->query("SELECT COUNT(*) FROM wht_sections")->fetchColumn() . "\n";
echo "  distinct section: " . $dst->query("SELECT COUNT(DISTINCT section) FROM wht_sections")->fetchColumn() . "\n";
echo "  by applies_to   : ";
foreach ($dst->query("SELECT applies_to, COUNT(*) c FROM wht_sections GROUP BY applies_to") as $r) {
    echo "{$r['applies_to']}={$r['c']} ";
}

echo "\n\n  duplicate section labels (same section, many codes):\n";
$dup = $dst->query("SELECT section, COUNT(*) c FROM wht_sections GROUP BY section HAVING c > 1 ORDER BY c DESC LIMIT 15");
foreach ($dup as $r) {
    printf("    %-14s %d codes\n", $r['section'], $r['c']);
}

echo "\n\nIMPORTED TOTALS (new tables)\n";
foreach (['wht_companies','wht_parties','wht_purchases','wht_salaries','wht_tax_rates','wht_salary_slabs','wht_challans'] as $t) {
    printf("  %-20s %s\n", $t, $dst->query("SELECT COUNT(*) FROM `$t`")->fetchColumn());
}

echo "\nSANITY: totals old vs new\n";
$o = $src->query("SELECT COUNT(*) n, ROUND(SUM(tax_withheld),2) t FROM transactions_purchases")->fetch();
$n = $dst->query("SELECT COUNT(*) n, ROUND(SUM(tax_withheld),2) t FROM wht_purchases")->fetch();
printf("  purchases  old: %s rows / %s tax   new: %s rows / %s tax  %s\n",
    $o['n'], $o['t'], $n['n'], $n['t'], ($o['n']==$n['n'] && $o['t']==$n['t']) ? 'MATCH' : 'MISMATCH');
$o = $src->query("SELECT COUNT(*) n, ROUND(SUM(tax_deducted),2) t FROM transactions_salaries")->fetch();
$n = $dst->query("SELECT COUNT(*) n, ROUND(SUM(tax_deducted),2) t FROM wht_salaries")->fetch();
printf("  salaries   old: %s rows / %s tax   new: %s rows / %s tax  %s\n",
    $o['n'], $o['t'], $n['n'], $n['t'], ($o['n']==$n['n'] && $o['t']==$n['t']) ? 'MATCH' : 'MISMATCH');

echo "\nTAX RATES imported\n";
foreach ($dst->query("SELECT section, category, atl_status, rate FROM wht_tax_rates ORDER BY section") as $r) {
    printf("  %-14s %-12s %-10s %s%%\n", $r['section'], $r['category'], $r['atl_status'], rtrim(rtrim($r['rate'],'0'),'.'));
}

echo "\nSALARY SLABS by year\n";
foreach ($dst->query("SELECT tax_year, COUNT(*) c FROM wht_salary_slabs GROUP BY tax_year ORDER BY tax_year") as $r) {
    printf("  TY%s: %d slabs\n", $r['tax_year'], $r['c']);
}

echo "</pre>";
