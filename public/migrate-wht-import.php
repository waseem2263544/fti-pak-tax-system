<?php
/**
 * migrate-wht-import.php — bring the standalone WHT portal's data into the module.
 *
 * Both databases live on the same cPanel host, so this connects to each directly;
 * no dump file is needed. Run migrate-wht.php FIRST to create the tables.
 *
 * Idempotent: every row carries a legacy_id, and re-running updates rather than
 * duplicates. Add ?dry=1 to the URL to see what would happen without writing.
 *
 * Rates and slabs were per-company in the old portal and are global here, so they
 * are de-duplicated. Any disagreement between companies is reported at the end —
 * resolve those in Settings → Tax Rates.
 */
set_time_limit(900);
echo "<pre><h2>WHT Data Import</h2>\n";

$dry = isset($_GET['dry']);
if ($dry) {
    echo "DRY RUN — nothing will be written.\n\n";
}

// ── SOURCE (old portal) ──
$srcName = 'fairtax1_wht';
$srcUser = 'fairtax1_wht';
$srcPass = '47yTehPqSv63hSUVAnLn';

// Where the old portal's uploads/challans directory lives on this server.
// Leave as null to skip copying challan documents.
$oldUploadsDir = null; // e.g. '/home/fairtax1/wht.fairtaxint.com/uploads/challans'

// Rates in the old portal had no dates. Everything imported is treated as being
// in force from this month onwards, so historical transactions still resolve.
$rateStartMonth = '2000-07-01';

// ── DESTINATION (main app) ──
$env = [];
$envPath = __DIR__ . '/../.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim(trim($v), "\"'");
    }
}
$dstName = $env['DB_DATABASE'] ?? 'fairtax1_fti_pak';
$dstUser = $env['DB_USERNAME'] ?? 'fairtax1_fti_pak';
$dstPass = $env['DB_PASSWORD'] ?? 'Yousafzai1';

$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $src = new PDO("mysql:host=localhost;dbname=$srcName;charset=utf8mb4", $srcUser, $srcPass, $opts);
    $dst = new PDO("mysql:host=localhost;dbname=$dstName;charset=utf8mb4", $dstUser, $dstPass, $opts);
} catch (PDOException $e) {
    exit("Connection failed: " . htmlspecialchars($e->getMessage()) . "\n</pre>");
}

$now = date('Y-m-d H:i:s');
$warnings = [];

/** Does the source table exist? Some installs never ran every setup script. */
$srcHas = function (string $table) use ($src, $srcName): bool {
    $q = $src->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    $q->execute([$srcName, $table]);

    return (bool) $q->fetchColumn();
};

$srcRows = function (string $table) use ($src, $srcHas): array {
    return $srcHas($table) ? $src->query("SELECT * FROM `$table`")->fetchAll() : [];
};

// ── VALUE MAPPING ──
$mapType = fn($v) => match (strtolower(trim((string) $v))) {
    'employee' => 'employee', 'both' => 'both', default => 'vendor',
};
$mapCategory = fn($v) => match (strtolower(trim((string) $v))) {
    'company' => 'company', 'aop' => 'aop', default => 'individual',
};
// The old filer_status column meant ATL membership, despite the Active/Inactive wording.
$mapAtl = fn($v) => strtolower(trim((string) $v)) === 'inactive' ? 'non-filer' : 'filer';
$mapMode = fn($v) => strtolower(trim((string) $v)) === 'net' ? 'net' : 'gross';
$monthStart = fn($d) => $d ? date('Y-m-01', strtotime($d)) : null;

// ── 1. COMPANIES ──
echo "── Companies ──\n";
$companyMap = [];   // old id => new id
foreach ($srcRows('companies') as $c) {
    $existing = $dst->prepare("SELECT id FROM wht_companies WHERE legacy_id = ?");
    $existing->execute([$c['id']]);
    $newId = $existing->fetchColumn();

    if ($newId) {
        echo "  = {$c['name']} (already imported)\n";
    } elseif ($dry) {
        echo "  + {$c['name']}\n";
        $newId = 'DRY';
    } else {
        $ins = $dst->prepare("INSERT INTO wht_companies (name, ntn_cnic, address, logo_path, is_active, legacy_id, created_at, updated_at)
                              VALUES (?, ?, ?, ?, 1, ?, ?, ?)");
        $ins->execute([$c['name'], $c['ntn_cnic'], $c['address'], $c['logo_path'], $c['id'], $now, $now]);
        $newId = $dst->lastInsertId();
        echo "  + {$c['name']}\n";
    }

    $companyMap[$c['id']] = $newId;
}
echo count($companyMap) . " companies.\n\n";

// ── 2. SECTIONS (global, already seeded — fill any gaps) ──
echo "── Sections ──\n";
$added = 0;
$seenCodes = [];
foreach ($srcRows('tax_payment_sections') as $s) {
    if (isset($seenCodes[$s['code']])) continue;
    $seenCodes[$s['code']] = true;

    $applies = str_starts_with((string) $s['section'], '149') ? 'salary' : 'purchase';

    if (!$dry) {
        $ins = $dst->prepare("INSERT IGNORE INTO wht_sections (section, payment_nature, payment_section, code, applies_to, is_active, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, 1, ?, ?)");
        $ins->execute([$s['section'], $s['payment_nature'], $s['payment_section'], $s['code'], $applies, $now, $now]);
        $added += $ins->rowCount();
    }
}
echo "$added new sections (the rest were already seeded).\n\n";

// ── 3. TAX RATES (per-company → global) ──
echo "── Tax Rates ──\n";
$byKey = [];
foreach ($srcRows('tax_rules') as $r) {
    $key = implode('|', [
        $r['section'],
        $r['goods_type'] ?? '',
        $mapCategory($r['category']),
        $mapAtl($r['filer_status']),
    ]);
    $byKey[$key][] = ['rate' => (float) $r['tax_rate'], 'company' => $r['company_id']];
}

$rateCount = 0;
foreach ($byKey as $key => $entries) {
    [$section, $goods, $category, $atl] = explode('|', $key);

    $rates = array_column($entries, 'rate');
    $distinct = array_values(array_unique($rates));

    // Companies disagreed on a statutory rate — take the most common and flag it.
    if (count($distinct) > 1) {
        $counts = array_count_values(array_map('strval', $rates));
        arsort($counts);
        $chosen = (float) array_key_first($counts);
        $warnings[] = sprintf(
            'Rate conflict for %s / %s / %s: companies had %s — imported %s%%. Review in Settings → Tax Rates.',
            $section, $category, $atl, implode('%, ', $distinct) . '%', $chosen
        );
    } else {
        $chosen = $distinct[0];
    }

    if (!$dry) {
        // Skip if this exact rule already exists from a previous run.
        $chk = $dst->prepare("SELECT id FROM wht_tax_rates WHERE section = ? AND goods_type = ? AND category = ? AND atl_status = ? AND effective_from = ?");
        $chk->execute([$section, $goods, $category, $atl, $rateStartMonth]);

        if (!$chk->fetchColumn()) {
            $ins = $dst->prepare("INSERT INTO wht_tax_rates (section, goods_type, category, atl_status, rate, effective_from, effective_to, notes, created_at, updated_at)
                                  VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?)");
            $ins->execute([$section, $goods, $category, $atl, $chosen, $rateStartMonth, 'Imported from WHT portal', $now, $now]);
            $rateCount++;
        }
    } else {
        $rateCount++;
    }
}
echo "$rateCount rate rules (de-duplicated from " . array_sum(array_map('count', $byKey)) . " per-company rows).\n\n";

// ── 4. SALARY SLABS (per-company → global) ──
echo "── Salary Slabs ──\n";
$slabSeen = [];
$slabCount = 0;
foreach ($srcRows('tax_slabs') as $s) {
    $key = $s['tax_year'] . '|' . (float) $s['min_salary'];
    if (isset($slabSeen[$key])) continue;
    $slabSeen[$key] = true;

    if (!$dry) {
        $chk = $dst->prepare("SELECT id FROM wht_salary_slabs WHERE tax_year = ? AND min_salary = ?");
        $chk->execute([$s['tax_year'], $s['min_salary']]);

        if (!$chk->fetchColumn()) {
            $ins = $dst->prepare("INSERT INTO wht_salary_slabs (tax_year, min_salary, max_salary, fixed_tax, tax_rate, created_at, updated_at)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$s['tax_year'], $s['min_salary'], $s['max_salary'], $s['fixed_tax'], $s['tax_rate'], $now, $now]);
            $slabCount++;
        }
    } else {
        $slabCount++;
    }
}
echo "$slabCount slabs.\n\n";

// ── 5. PARTIES ──
echo "── Parties ──\n";
$partyMap = [];
$partyCount = 0;
foreach ($srcRows('parties') as $p) {
    if (!isset($companyMap[$p['company_id']])) {
        $warnings[] = "Party '{$p['name']}' belongs to unknown company {$p['company_id']} — skipped.";
        continue;
    }

    $chk = $dst->prepare("SELECT id FROM wht_parties WHERE legacy_id = ?");
    $chk->execute([$p['id']]);
    $newId = $chk->fetchColumn();

    if (!$newId && !$dry) {
        $ins = $dst->prepare("INSERT INTO wht_parties
            (wht_company_id, name, cnic_ntn, type, category, atl_status, is_active, city, address,
             default_section, default_goods_type, default_calc_mode, default_salary_amount, legacy_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $companyMap[$p['company_id']], $p['name'], $p['cnic_ntn'],
            $mapType($p['type']), $mapCategory($p['category']), $mapAtl($p['filer_status']),
            $p['city'] ?? null, $p['address'] ?? null,
            $p['default_section'] ?? null, $p['default_goods_type'] ?? null,
            $mapMode($p['default_calc_mode'] ?? 'Gross'),
            ($p['default_salary_amount'] ?? null) ?: null,
            $p['id'], $now, $now,
        ]);
        $newId = $dst->lastInsertId();
        $partyCount++;
    } elseif (!$newId) {
        $newId = 'DRY';
        $partyCount++;
    }

    $partyMap[$p['id']] = $newId;
}
echo "$partyCount parties imported (" . count($partyMap) . " mapped).\n\n";

// ── 6. PURCHASES ──
echo "── Payments ──\n";
$purchaseCount = 0;
foreach ($srcRows('transactions_purchases') as $t) {
    if (!isset($companyMap[$t['company_id']], $partyMap[$t['party_id']])) {
        $warnings[] = "Purchase #{$t['id']} references a missing company or party — skipped.";
        continue;
    }

    $chk = $dst->prepare("SELECT id FROM wht_purchases WHERE legacy_id = ?");
    $chk->execute([$t['id']]);
    if ($chk->fetchColumn()) continue;

    if ($dry) { $purchaseCount++; continue; }

    // The old schema had no separate tax period, so the payment month is used.
    $ins = $dst->prepare("INSERT INTO wht_purchases
        (wht_company_id, party_id, period_month, payment_date, section, goods_type, calc_mode,
         gross_amount, tax_rate, tax_rate_id, rate_source, tax_withheld, net_payment,
         cpr_no, cpr_date, psid_no, remarks, legacy_id, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'gross', ?, ?, NULL, 'matrix', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([
        $companyMap[$t['company_id']], $partyMap[$t['party_id']],
        $monthStart($t['payment_date']), $t['payment_date'],
        $t['section'] ?? null, $t['goods_type'] ?? null,
        $t['gross_amount'], $t['tax_rate'], $t['tax_withheld'], $t['net_payment'],
        $t['cpr_no'] ?? null, $t['cpr_date'] ?? null, $t['psid_no'] ?? null, $t['remarks'] ?? null,
        $t['id'], $t['created_at'] ?? $now, $now,
    ]);
    $purchaseCount++;
}
echo "$purchaseCount payments.\n\n";

// ── 7. SALARIES ──
echo "── Salaries ──\n";
$salaryCount = 0;
foreach ($srcRows('transactions_salaries') as $t) {
    if (!isset($companyMap[$t['company_id']], $partyMap[$t['employee_id']])) {
        $warnings[] = "Salary #{$t['id']} references a missing company or employee — skipped.";
        continue;
    }

    $chk = $dst->prepare("SELECT id FROM wht_salaries WHERE legacy_id = ?");
    $chk->execute([$t['id']]);
    if ($chk->fetchColumn()) continue;

    if ($dry) { $salaryCount++; continue; }

    // Recover the exempt rate actually used, rather than assuming 10%.
    $taxable = (float) $t['taxable_salary'];
    $exemptRate = $taxable > 0 ? round(((float) $t['medical_allowance'] / $taxable) * 100, 2) : 10.0;

    $month = $monthStart($t['salary_month']);
    $taxYear = (int) date('n', strtotime($month)) >= 7
        ? (int) date('Y', strtotime($month)) + 1
        : (int) date('Y', strtotime($month));

    $ins = $dst->prepare("INSERT INTO wht_salaries
        (wht_company_id, employee_id, salary_month, payment_date, section, calc_mode, input_amount,
         taxable_salary, exempt_amount, exempt_rate, total_salary, tax_deducted, final_net_payment,
         tax_year, cpr_no, psid_no, challan_date, legacy_id, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'gross', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([
        $companyMap[$t['company_id']], $partyMap[$t['employee_id']],
        $month, $t['payment_date'], $t['section'] ?? '149',
        $t['input_net_payment'], $t['taxable_salary'], $t['medical_allowance'], $exemptRate,
        $t['total_salary'], $t['tax_deducted'], $t['final_net_payment'], $taxYear,
        $t['cpr_no'] ?? null, $t['psid_no'] ?? null, $t['challan_date'] ?? null,
        $t['id'], $t['created_at'] ?? $now, $now,
    ]);
    $salaryCount++;
}
echo "$salaryCount salary records.\n\n";

// ── 8. CHALLAN DOCUMENTS ──
echo "── Challans ──\n";
$challanCount = 0;
$fileCount = 0;
$storageBase = __DIR__ . '/../storage/app/wht-challans';

foreach ($srcRows('challan_files') as $c) {
    if (!isset($companyMap[$c['company_id']])) continue;

    $newCompanyId = $companyMap[$c['company_id']];

    $chk = $dst->prepare("SELECT id FROM wht_challans WHERE wht_company_id = ? AND psid_no = ?");
    $chk->execute([$newCompanyId, $c['psid_no']]);
    if ($chk->fetchColumn()) continue;

    if ($dry) { $challanCount++; continue; }

    // Move the documents out of the old webroot into private storage.
    $stored = ['psid_file' => null, 'cpr_file' => null];

    foreach (['psid_file', 'cpr_file'] as $field) {
        if (empty($c[$field]) || !$oldUploadsDir) continue;

        $source = rtrim($oldUploadsDir, '/') . '/' . basename($c[$field]);
        if (!is_readable($source)) {
            $warnings[] = "Challan file not found: $source";
            continue;
        }

        $destDir = "$storageBase/$newCompanyId";
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

        $name = basename($c[$field]);
        if (copy($source, "$destDir/$name")) {
            $stored[$field] = "wht-challans/$newCompanyId/$name";
            $fileCount++;
        }
    }

    $ins = $dst->prepare("INSERT INTO wht_challans (wht_company_id, psid_no, cpr_no, psid_file, cpr_file, legacy_id, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([$newCompanyId, $c['psid_no'], $c['cpr_no'], $stored['psid_file'], $stored['cpr_file'],
                   $c['id'], $c['uploaded_at'] ?? $now, $now]);
    $challanCount++;
}
echo "$challanCount challans, $fileCount documents copied.\n";
if (!$oldUploadsDir) {
    echo "  (set \$oldUploadsDir at the top of this file to copy the PDFs too)\n";
}
echo "\n";

// ── 9. USER ACCESS ──
echo "── User Access ──\n";
$grantCount = 0;
if ($srcHas('user_permissions')) {
    // Old portal users are matched to app users by email.
    $oldUsers = [];
    foreach ($srcRows('users') as $u) {
        $oldUsers[$u['id']] = strtolower(trim($u['email']));
    }

    foreach ($srcRows('user_permissions') as $p) {
        $email = $oldUsers[$p['user_id']] ?? null;
        if (!$email || !isset($companyMap[$p['company_id']])) continue;

        $find = $dst->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
        $find->execute([$email]);
        $userId = $find->fetchColumn();

        if (!$userId) {
            $warnings[] = "No app user matches '$email' — access grant skipped.";
            continue;
        }

        if ($dry) { $grantCount++; continue; }

        $ins = $dst->prepare("INSERT IGNORE INTO wht_company_user
            (wht_company_id, user_id, can_view, can_create, can_edit, can_delete, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $companyMap[$p['company_id']], $userId,
            (int) $p['can_view'], (int) $p['can_create'], (int) $p['can_edit'], (int) $p['can_delete'],
            $now, $now,
        ]);
        $grantCount += $ins->rowCount();
    }
}
echo "$grantCount access grants.\n\n";

// ── SUMMARY ──
echo str_repeat('=', 60) . "\n";
echo $dry ? "DRY RUN COMPLETE — nothing written\n" : "IMPORT COMPLETE\n";
echo str_repeat('=', 60) . "\n";

if ($warnings) {
    echo "\n" . count($warnings) . " thing(s) need your attention:\n\n";
    foreach (array_unique($warnings) as $w) {
        echo "  ! $w\n";
    }
}

echo "\nNext steps:\n";
echo "  1. Open Withholding Tax → Tax Rates and set proper effective months.\n";
echo "     Everything imported starts from $rateStartMonth so old entries resolve.\n";
echo "  2. Check Salary Slabs has a set for each tax year you file.\n";
echo "  3. Spot-check a few payments against the old portal.\n";
echo "  4. Delete this file and migrate-wht.php from the server.\n";
echo "</pre>";
