<?php
/**
 * wht-rate-timeline.php — reconstruct the historical rate timeline from the
 * transactions themselves.
 *
 * The old portal held one current rate per combination, so it could not record
 * that a rate changed. The transactions did keep the rate each was computed at,
 * which is enough to rebuild the effective-dated matrix.
 *
 * Read-only: this proposes, it does not write. Delete after use.
 */
set_time_limit(300);
echo "<pre><h2>Rate Timeline (proposal)</h2>\n";

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$db = new PDO("mysql:host=localhost;dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$rows = $db->query("SELECT p.section, t.category, t.atl_status,
                           DATE_FORMAT(p.period_month, '%Y-%m') AS ym,
                           p.tax_rate, COUNT(*) c, ROUND(SUM(p.tax_withheld)) tax
                    FROM wht_purchases p JOIN wht_parties t ON t.id = p.party_id
                    GROUP BY p.section, t.category, t.atl_status, ym, p.tax_rate
                    ORDER BY p.section, t.category, t.atl_status, ym, p.tax_rate")->fetchAll();

$groups = [];
foreach ($rows as $r) {
    $groups[$r['section'] . '|' . $r['category'] . '|' . $r['atl_status']][] = $r;
}

$fmt = fn($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.') . '%';

foreach ($groups as $key => $items) {
    [$section, $category, $atl] = explode('|', $key);
    printf("\n%s · %s · %s\n", $section, $category, $atl);
    echo str_repeat('-', 74) . "\n";

    // Month-by-month, so a month using two rates is visible rather than hidden.
    $byMonth = [];
    foreach ($items as $i) {
        $byMonth[$i['ym']][] = $i;
    }
    ksort($byMonth);

    foreach ($byMonth as $ym => $list) {
        $parts = [];
        foreach ($list as $l) {
            $parts[] = sprintf('%s (%d entries, tax %s)', $fmt($l['tax_rate']), $l['c'], number_format((float) $l['tax']));
        }
        printf("  %-9s %s\n", $ym, implode('   +   ', $parts));
    }

    // Collapse into contiguous runs of a single rate — the proposed windows.
    $runs = [];
    foreach ($byMonth as $ym => $list) {
        if (count($list) > 1) {
            $runs[] = ['ym' => $ym, 'rate' => null, 'mixed' => array_map(fn($l) => $l['tax_rate'], $list)];
            continue;
        }
        $rate = $list[0]['tax_rate'];
        $last = end($runs);
        if ($last && $last['rate'] !== null && abs((float) $last['rate'] - (float) $rate) < 0.0005) {
            $runs[key($runs)]['to'] = $ym;
        } else {
            $runs[] = ['ym' => $ym, 'to' => $ym, 'rate' => $rate];
        }
    }

    echo "\n  Proposed windows:\n";
    foreach ($runs as $r) {
        if ($r['rate'] === null) {
            printf("    %-9s AMBIGUOUS — that month used %s\n", $r['ym'],
                implode(' and ', array_map($fmt, $r['mixed'])));
        } else {
            printf("    %s → %-9s %s\n", $r['ym'], $r['to'] ?? $r['ym'], $fmt($r['rate']));
        }
    }

    $current = $db->prepare("SELECT rate FROM wht_tax_rates WHERE section=? AND category=? AND atl_status=? AND effective_to IS NULL");
    $current->execute([$section, $category, $atl]);
    $c = $current->fetchColumn();
    printf("\n  Matrix currently says: %s\n", $c === false ? 'no rule at all' : $fmt($c));
}

echo "\n" . str_repeat('=', 74) . "\n";
echo "Nothing written. These are the rates your own transactions were computed at.\n";
echo "</pre>";
