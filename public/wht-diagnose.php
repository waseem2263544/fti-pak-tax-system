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

echo "OLD portal — every section in the 153 / 233 / 149 families\n";
echo str_repeat('-', 96) . "\n";
$q = $src->query("SELECT company_id, section, payment_nature, payment_section, code
                  FROM tax_payment_sections
                  WHERE section LIKE '153%' OR section LIKE '233%' OR section LIKE '149%'
                  ORDER BY section, code");
foreach ($q as $r) {
    printf("  co%-3s %-16s %-30s %-36s %s\n",
        $r['company_id'], $r['section'], mb_substr($r['payment_nature'], 0, 28),
        mb_substr($r['payment_section'], 0, 34), $r['code']);
}

echo "\n\nOLD portal — the raw tax_rules rows\n";
echo str_repeat('-', 96) . "\n";
foreach ($src->query("SELECT * FROM tax_rules ORDER BY section") as $r) {
    printf("  co%-3s %-16s goods=%-16s %-12s %-10s %s%%\n",
        $r['company_id'], $r['section'], $r['goods_type'] ?: '-',
        $r['category'], $r['filer_status'], $r['tax_rate']);
}

echo "\n\nOLD portal — section values actually used by transactions\n";
echo str_repeat('-', 96) . "\n";
$q = $src->query("SELECT section, COUNT(*) c, ROUND(SUM(tax_withheld)) tax
                  FROM transactions_purchases GROUP BY section ORDER BY c DESC");
foreach ($q as $r) {
    printf("  %-18s %4d entries   tax %s\n", $r['section'] ?: '(blank)', $r['c'], number_format($r['tax']));
}
$q = $src->query("SELECT section, COUNT(*) c FROM transactions_salaries GROUP BY section ORDER BY c DESC");
foreach ($q as $r) {
    printf("  %-18s %4d salary entries\n", $r['section'] ?: '(blank)', $r['c']);
}

echo "\n\nDo the three orphan rate sections exist anywhere in the old data?\n";
echo str_repeat('-', 96) . "\n";
foreach (['153(1)(a)/6', '153(1)(a)/9', '153(1)(a)/31'] as $s) {
    $c = $src->prepare("SELECT COUNT(*) FROM tax_payment_sections WHERE section = ?");
    $c->execute([$s]);
    printf("  %-16s in old tax_payment_sections: %s\n", $s, $c->fetchColumn() ? 'YES' : 'NO — never existed');
}

echo "</pre>";
