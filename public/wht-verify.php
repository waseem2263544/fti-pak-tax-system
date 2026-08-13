<?php
/**
 * wht-verify.php — end-to-end check of the imported module.
 *
 * Re-resolves every imported transaction against the live rate matrix and
 * compares with the rate it was saved at. A mismatch means recording the same
 * payment today would compute differently — i.e. the matrix has a gap.
 *
 * Read-only. Delete this file after use.
 */
set_time_limit(300);
echo "<pre><h2>WHT Verification</h2>\n";

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$db = new PDO("mysql:host=localhost;dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// ── Rate resolution, mirroring WhtTaxRate::resolve() ──
$resolve = function (?string $section, ?string $category, ?string $atl, string $month) use ($db) {
    if (!$section || !$category || !$atl) return null;
    $q = $db->prepare("SELECT rate FROM wht_tax_rates
        WHERE section = ? AND category = ? AND atl_status = ? AND goods_type = ''
          AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?)
        ORDER BY effective_from DESC LIMIT 1");
    $q->execute([$section, $category, $atl, $month, $month]);
    $v = $q->fetchColumn();

    return $v === false ? null : (float) $v;
};

echo "── Purchases: stored rate vs what the matrix would give today ──\n";
$rows = $db->query("SELECT p.id, p.section, p.period_month, p.tax_rate, p.gross_amount, p.tax_withheld,
                           t.name AS party, t.category, t.atl_status
                    FROM wht_purchases p JOIN wht_parties t ON t.id = p.party_id
                    ORDER BY p.period_month")->fetchAll();

$match = 0; $mismatch = []; $noRule = [];
foreach ($rows as $r) {
    $live = $resolve($r['section'], $r['category'], $r['atl_status'], substr($r['period_month'], 0, 7) . '-01');

    if ($live === null) {
        $noRule[$r['section'] . ' / ' . $r['category'] . ' / ' . $r['atl_status']][] = $r['id'];
    } elseif (abs($live - (float) $r['tax_rate']) < 0.0005) {
        $match++;
    } else {
        $mismatch[] = sprintf('#%s %s %s %s: stored %s%% vs matrix %s%%',
            $r['id'], $r['section'], $r['category'], $r['atl_status'], rtrim(rtrim($r['tax_rate'],'0'),'.'), $live);
    }
}

printf("  %d of %d resolve to the same rate\n", $match, count($rows));

if ($noRule) {
    echo "\n  No matrix rule (would compute 0%% on a new entry):\n";
    foreach ($noRule as $key => $ids) {
        printf("    %-46s %d transaction(s)\n", $key, count($ids));
    }
}

if ($mismatch) {
    echo "\n  Rate differs from the matrix:\n";
    foreach (array_slice($mismatch, 0, 20) as $m) echo "    $m\n";
    if (count($mismatch) > 20) echo "    … and " . (count($mismatch) - 20) . " more\n";
}

// ── Arithmetic integrity of what was imported ──
echo "\n── Arithmetic check on imported rows ──\n";
$bad = $db->query("SELECT COUNT(*) FROM wht_purchases
    WHERE ABS(gross_amount - tax_withheld - net_payment) > 1.00")->fetchColumn();
echo "  purchases where gross - tax != net (>1.00): $bad\n";

$bad = $db->query("SELECT COUNT(*) FROM wht_salaries
    WHERE ABS(total_salary - tax_deducted - final_net_payment) > 1.00")->fetchColumn();
echo "  salaries where total - tax != net (>1.00): $bad\n";

$bad = $db->query("SELECT COUNT(*) FROM wht_salaries
    WHERE ABS(taxable_salary + exempt_amount - total_salary) > 1.00")->fetchColumn();
echo "  salaries where taxable + exempt != total (>1.00): $bad\n";

// ── Salary slab coverage ──
echo "\n── Salary slab coverage ──\n";
$q = $db->query("SELECT s.tax_year, COUNT(*) c,
                        (SELECT COUNT(*) FROM wht_salary_slabs sl WHERE sl.tax_year = s.tax_year) slabs
                 FROM wht_salaries s GROUP BY s.tax_year ORDER BY s.tax_year");
foreach ($q as $r) {
    printf("  TY%s: %d salary records, %d slabs %s\n", $r['tax_year'], $r['c'], $r['slabs'],
        $r['slabs'] ? '' : '  <-- MISSING, new entries would compute zero tax');
}

// ── Orphans ──
echo "\n── Referential checks ──\n";
foreach ([
    'purchases with no party'  => "SELECT COUNT(*) FROM wht_purchases p LEFT JOIN wht_parties t ON t.id=p.party_id WHERE t.id IS NULL",
    'salaries with no employee' => "SELECT COUNT(*) FROM wht_salaries s LEFT JOIN wht_parties t ON t.id=s.employee_id WHERE t.id IS NULL",
    'parties with no company'   => "SELECT COUNT(*) FROM wht_parties t LEFT JOIN wht_companies c ON c.id=t.wht_company_id WHERE c.id IS NULL",
    'purchases with blank section' => "SELECT COUNT(*) FROM wht_purchases WHERE section IS NULL OR section = ''",
] as $label => $sql) {
    printf("  %-32s %s\n", $label, $db->query($sql)->fetchColumn());
}

// ── Totals by company ──
echo "\n── Totals by withholding agent ──\n";
$q = $db->query("SELECT c.name,
    (SELECT COUNT(*) FROM wht_parties WHERE wht_company_id=c.id) parties,
    (SELECT COUNT(*) FROM wht_purchases WHERE wht_company_id=c.id) purch,
    (SELECT ROUND(SUM(tax_withheld)) FROM wht_purchases WHERE wht_company_id=c.id) ptax,
    (SELECT COUNT(*) FROM wht_salaries WHERE wht_company_id=c.id) sal,
    (SELECT ROUND(SUM(tax_deducted)) FROM wht_salaries WHERE wht_company_id=c.id) stax
    FROM wht_companies c ORDER BY c.name");
foreach ($q as $r) {
    printf("  %-28s %2d parties · %3d payments (tax %s) · %2d salaries (tax %s)\n",
        $r['name'], $r['parties'], $r['purch'], number_format((float)$r['ptax']),
        $r['sal'], number_format((float)$r['stax']));
}

echo "</pre>";
