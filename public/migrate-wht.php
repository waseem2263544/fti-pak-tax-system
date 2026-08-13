<?php
/**
 * migrate-wht.php — Withholding Tax (WHT) module setup.
 *
 * Creates the wht_* tables and seeds the global FBR section list.
 * Safe to re-run: every statement is CREATE TABLE IF NOT EXISTS / INSERT IGNORE.
 *
 * Rates, sections and salary slabs are GLOBAL (no company_id) — they are
 * statutory, not per-client. Only parties, transactions and challans are
 * scoped to a wht_company.
 */
set_time_limit(300);
echo "<pre><h2>WHT Module Setup</h2>\n";

// ── DB CONNECTION ──
// Prefer .env so credentials are not duplicated in source; fall back to the
// values the other migrate-*.php scripts use.
$env = [];
$envPath = __DIR__ . '/../.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim(trim($v), "\"'");
    }
}
$dbHost = $env['DB_HOST']     ?? 'localhost';
$dbName = $env['DB_DATABASE'] ?? 'fairtax1_fti_pak';
$dbUser = $env['DB_USERNAME'] ?? 'fairtax1_fti_pak';
$dbPass = $env['DB_PASSWORD'] ?? 'Yousafzai1';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$now = date('Y-m-d H:i:s');

// ── TABLES ──
$tables = [

// Withholding agents. Kept separate from `clients` — a withholding agent is not
// necessarily a client of the firm. `legacy_id` maps back to the old portal's
// companies.id so the importer stays idempotent.
"CREATE TABLE IF NOT EXISTS `wht_companies` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `ntn_cnic` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `logo_path` VARCHAR(255) NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `legacy_id` INT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_company_legacy` (`legacy_id`),
  INDEX `idx_wht_company_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// Per-company access. Mirrors the old portal's user_permissions table so those
// grants survive the import; module-wide access is still gated by app roles.
"CREATE TABLE IF NOT EXISTS `wht_company_user` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wht_company_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `can_view` TINYINT(1) NOT NULL DEFAULT 1,
  `can_create` TINYINT(1) NOT NULL DEFAULT 0,
  `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_company_user` (`wht_company_id`, `user_id`),
  FOREIGN KEY (`wht_company_id`) REFERENCES `wht_companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// Vendors and employees. The old portal's single `filer_status` column meant
// both 'on the ATL' and 'this party is active' — which is why non-filer vendors
// could never be picked. Split into `atl_status` and `is_active`.
"CREATE TABLE IF NOT EXISTS `wht_parties` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wht_company_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `cnic_ntn` VARCHAR(20) NULL,
  `type` ENUM('vendor','employee','both') NOT NULL DEFAULT 'vendor',
  `category` ENUM('company','individual','aop') NOT NULL DEFAULT 'individual',
  `atl_status` ENUM('filer','non-filer') NOT NULL DEFAULT 'filer',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `city` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `default_section` VARCHAR(50) NULL,
  `default_goods_type` VARCHAR(255) NULL,
  `default_calc_mode` ENUM('gross','net') NOT NULL DEFAULT 'gross',
  `default_salary_amount` DECIMAL(15,2) NULL,
  `exempt_rate` DECIMAL(5,2) NULL,
  `legacy_id` INT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_party_legacy` (`legacy_id`),
  INDEX `idx_wht_party_company` (`wht_company_id`),
  INDEX `idx_wht_party_type` (`type`),
  FOREIGN KEY (`wht_company_id`) REFERENCES `wht_companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// GLOBAL: FBR payment sections (u/s heads and their PSID payment codes).
"CREATE TABLE IF NOT EXISTS `wht_sections` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `section` VARCHAR(50) NOT NULL,
  `payment_nature` VARCHAR(150) NOT NULL,
  `payment_section` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `applies_to` ENUM('purchase','salary','both') NOT NULL DEFAULT 'purchase',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_section_code` (`code`),
  INDEX `idx_wht_section` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// GLOBAL: the rate matrix, versioned by tax period month.
// A rate applies to a transaction whose PERIOD MONTH falls inside the window —
// not its payment date. Deposit a June liability in August and June's rate wins.
// effective_from / effective_to are stored as the first day of the month.
"CREATE TABLE IF NOT EXISTS `wht_tax_rates` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `section` VARCHAR(50) NOT NULL,
  `goods_type` VARCHAR(255) NOT NULL DEFAULT '',
  `category` ENUM('company','individual','aop') NOT NULL,
  `atl_status` ENUM('filer','non-filer') NOT NULL,
  `rate` DECIMAL(6,3) NOT NULL DEFAULT 0.000,
  `effective_from` DATE NOT NULL,
  `effective_to` DATE NULL,
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_wht_rate_lookup` (`section`, `category`, `atl_status`, `effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// GLOBAL: salary tax slabs, already keyed by tax year.
"CREATE TABLE IF NOT EXISTS `wht_salary_slabs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `min_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `max_salary` DECIMAL(15,2) NULL,
  `fixed_tax` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(6,3) NOT NULL DEFAULT 0.000,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_wht_slab_year` (`tax_year`, `min_salary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// Purchase / service / contract payments.
// `period_month` drives rate selection; `payment_date` is when cash moved.
// `tax_rate` and `tax_rate_id` snapshot which rule was applied, so correcting a
// rate later never silently rewrites history.
"CREATE TABLE IF NOT EXISTS `wht_purchases` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wht_company_id` BIGINT UNSIGNED NOT NULL,
  `party_id` BIGINT UNSIGNED NOT NULL,
  `period_month` DATE NOT NULL,
  `payment_date` DATE NOT NULL,
  `section` VARCHAR(50) NULL,
  `goods_type` VARCHAR(255) NULL,
  `calc_mode` ENUM('gross','net') NOT NULL DEFAULT 'gross',
  `gross_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(6,3) NOT NULL DEFAULT 0.000,
  `tax_rate_id` BIGINT UNSIGNED NULL,
  `rate_source` ENUM('matrix','manual') NOT NULL DEFAULT 'manual',
  `tax_withheld` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `net_payment` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `cpr_no` VARCHAR(50) NULL,
  `cpr_date` DATE NULL,
  `psid_no` VARCHAR(50) NULL,
  `remarks` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `legacy_id` INT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_purchase_legacy` (`legacy_id`),
  INDEX `idx_wht_purchase_company` (`wht_company_id`, `period_month`),
  INDEX `idx_wht_purchase_party` (`party_id`),
  INDEX `idx_wht_purchase_psid` (`psid_no`),
  FOREIGN KEY (`wht_company_id`) REFERENCES `wht_companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`party_id`) REFERENCES `wht_parties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// Salary payments. `salary_month` is the period month for slab/rate selection.
"CREATE TABLE IF NOT EXISTS `wht_salaries` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wht_company_id` BIGINT UNSIGNED NOT NULL,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `salary_month` DATE NOT NULL,
  `payment_date` DATE NOT NULL,
  `section` VARCHAR(50) NULL,
  `calc_mode` ENUM('gross','net') NOT NULL DEFAULT 'gross',
  `input_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `taxable_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `exempt_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `exempt_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `total_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_deducted` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `final_net_payment` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_year` SMALLINT UNSIGNED NULL,
  `cpr_no` VARCHAR(50) NULL,
  `cpr_date` DATE NULL,
  `psid_no` VARCHAR(50) NULL,
  `challan_date` DATE NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `legacy_id` INT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_salary_legacy` (`legacy_id`),
  INDEX `idx_wht_salary_company` (`wht_company_id`, `salary_month`),
  INDEX `idx_wht_salary_employee` (`employee_id`),
  INDEX `idx_wht_salary_psid` (`psid_no`),
  FOREIGN KEY (`wht_company_id`) REFERENCES `wht_companies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `wht_parties`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// PSID / CPR documents. Files live under storage/app/wht-challans (private),
// not in the webroot as the old portal had them.
"CREATE TABLE IF NOT EXISTS `wht_challans` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wht_company_id` BIGINT UNSIGNED NOT NULL,
  `psid_no` VARCHAR(100) NOT NULL,
  `cpr_no` VARCHAR(100) NULL,
  `psid_file` VARCHAR(255) NULL,
  `cpr_file` VARCHAR(255) NULL,
  `paid_on` DATE NULL,
  `legacy_id` INT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_challan` (`wht_company_id`, `psid_no`),
  FOREIGN KEY (`wht_company_id`) REFERENCES `wht_companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// Module-wide defaults (exempt allowance %, certificate prefix, ...).
"CREATE TABLE IF NOT EXISTS `wht_settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_wht_setting_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

];

echo "Creating tables...\n";
foreach ($tables as $sql) {
    $pdo->exec($sql);
    preg_match('/EXISTS `(\w+)`/', $sql, $m);
    echo "  ok  {$m[1]}\n";
}

// ── SEED: GLOBAL FBR SECTIONS ──
// [section, payment nature, payment section, PSID code, applies_to]
$sections = [
    ['148', 'Imports', 'Imports', '64060001', 'purchase'],
    ['149', 'Salary', 'Salary', '64060002', 'salary'],
    ['150', 'Dividend', 'Dividend', '64060003', 'purchase'],
    ['150A', 'Return on Investment in Sukuks', 'Sukuk ROI', '64060004', 'purchase'],
    ['151', 'Profit on Debt', 'Profit on Debt', '64060005', 'purchase'],
    ['152(1)', 'Payments to Non-Residents', 'Royalty / Fee for Technical Services', '64060006', 'purchase'],
    ['152(1A)', 'Payments to Non-Residents', 'Construction / Assembly / Execution of Contracts', '64060007', 'purchase'],
    ['152(2)', 'Payments to Non-Residents', 'Other Payments', '64060008', 'purchase'],
    ['153(1)(a)', 'Sale of Goods', 'Payment for Goods', '64060009', 'purchase'],
    ['153(1)(b)', 'Services', 'Payment for Services', '64060010', 'purchase'],
    ['153(1)(c)', 'Execution of Contracts', 'Payment for Contracts', '64060011', 'purchase'],
    ['154', 'Exports', 'Export Proceeds', '64060012', 'purchase'],
    ['154A', 'Export of Services', 'Export of Services', '64060013', 'purchase'],
    ['155', 'Rent of Immovable Property', 'Rent', '64060014', 'purchase'],
    ['156', 'Prize and Winnings', 'Prize Bonds / Lottery', '64060015', 'purchase'],
    ['156A', 'Petroleum Products', 'Petroleum Products', '64060016', 'purchase'],
    ['156B', 'Withdrawal of Balance from Pension Fund', 'Pension Fund Withdrawal', '64060017', 'purchase'],
    ['158', 'Dist. of Goods by Manuf.', 'Distribution by Manufacturer', '64060018', 'purchase'],
    ['231A', 'Cash Withdrawal from Bank', 'Cash Withdrawal', '64060019', 'purchase'],
    ['231AA', 'Advance Tax on Transactions in Bank', 'Banking Transactions', '64060020', 'purchase'],
    ['231B', 'Purchase of Motor Vehicles', 'Purchase of Motor Vehicles', '64060049', 'purchase'],
    ['233', 'Brokerage & Commission', 'Brokerage / Commission', '64060021', 'purchase'],
    ['233A', 'Collection by Stock Exchange', 'Stock Exchange Collection', '64060022', 'purchase'],
    ['233AA', 'Collection by NCCPL', 'NCCPL Collection', '64060023', 'purchase'],
    ['234', 'Transport Vehicles', 'Transport Vehicles', '64060024', 'purchase'],
    ['234A', 'CNG Stations', 'CNG Stations', '64060025', 'purchase'],
    ['235', 'Electricity Consumption', 'Electricity Bill', '64060026', 'purchase'],
    ['235A', 'Domestic Electricity Consumption', 'Domestic Electricity', '64060027', 'purchase'],
    ['235B', 'Steel Melters and Composite Units', 'Steel Melters', '64060028', 'purchase'],
    ['236', 'Telephone and Internet Users', 'Telephone / Internet Bill', '64060029', 'purchase'],
    ['236A', 'Sale by Public Auction', 'Public Auction', '64060030', 'purchase'],
    ['236B', 'Air Travel', 'Air Travel (Domestic/Intl)', '64060031', 'purchase'],
    ['236C', 'Sale/Transfer of Immovable Property', 'Sale of Property', '64060032', 'purchase'],
    ['236D', 'Functions/Gatherings', 'Marriage Halls / Functions', '64060033', 'purchase'],
    ['236F', 'Cable Operators', 'Cable TV Operators', '64060034', 'purchase'],
    ['236G', 'Sale to Distributors/Dealers/Wholesalers', 'Sale to Distributors', '64060035', 'purchase'],
    ['236H', 'Sale to Retailers', 'Sale to Retailers', '64060036', 'purchase'],
    ['236I', 'Collection by Edu. Institutions', 'Educational Fees', '64060037', 'purchase'],
    ['236J', 'Dealers/Commission Agents/Arhatis', 'Dealers / Arhatis', '64060038', 'purchase'],
    ['236K', 'Purchase/Transfer of Immovable Property', 'Purchase of Property', '64060039', 'purchase'],
    ['236L', 'International Air Ticket', 'Intl Air Ticket', '64060040', 'purchase'],
    ['236P', 'Banking Transactions of Non-Filers', 'Banking Tx Non-Filers', '64060041', 'purchase'],
    ['236Q', 'Rent of Machinery/Equip.', 'Rent of Machinery', '64060042', 'purchase'],
    ['236R', 'Remittance of Edu. Expenses Abroad', 'Edu Remittance', '64060043', 'purchase'],
    ['236U', 'Insurance Premium', 'General / Life Insurance', '64060044', 'purchase'],
    ['236V', 'Extraction of Minerals', 'Minerals Extraction', '64060045', 'purchase'],
    ['236W', 'Purchase/Transfer of Immovable Property (Non-Resident)', 'Purchase by Non-Resident', '64060046', 'purchase'],
    ['236Y', 'Persons Remitting Amounts Abroad through Card', 'Card Remittance Abroad', '64060047', 'purchase'],
    ['236Z', 'Withdrawal of Balance from Pension Fund', 'Pension Fund Withdrawal (236Z)', '64060048', 'purchase'],
    // Salary sub-heads carried over from the old portal's seed_salary_sections.php
    ['149', 'Salary', 'Salary', '14901', 'salary'],
    ['149', 'Salary', 'Arrears of Salary', '14902', 'salary'],
    ['149', 'Salary', 'Employee Share Scheme', '14903', 'salary'],
];

echo "\nSeeding sections...\n";
$ins = $pdo->prepare("INSERT IGNORE INTO wht_sections
    (section, payment_nature, payment_section, code, applies_to, is_active, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, 1, ?, ?)");
foreach ($sections as $s) {
    $ins->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $now, $now]);
}
echo count($sections) . " sections seeded.\n";

// ── SEED: DEFAULT SETTINGS ──
$settings = [
    ['exempt_allowance_rate', '10'],          // medical allowance, % of taxable salary
    ['certificate_prefix', 'WHT'],
    ['default_calc_mode', 'gross'],
    ['statement_footer', 'This is a computer generated certificate and does not require a stamp.'],
];

echo "\nSeeding settings...\n";
$ins = $pdo->prepare("INSERT IGNORE INTO wht_settings (`key`, `value`, created_at, updated_at) VALUES (?, ?, ?, ?)");
foreach ($settings as $s) {
    $ins->execute([$s[0], $s[1], $now, $now]);
}
echo count($settings) . " settings seeded.\n";

echo "\n========================================\n";
echo "WHT MODULE SETUP COMPLETE\n";
echo "========================================\n";
echo "Tables: " . count($tables) . "\n";
echo "Sections: " . count($sections) . " (global)\n";
echo "Settings: " . count($settings) . "\n";
echo "\nNext: run migrate-wht-import.php to bring data over from the old portal.\n";
echo "</pre>";
