<?php
set_time_limit(300);
echo "<pre><h2>Accounting Module Setup</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── TABLES ──
$tables = [

"CREATE TABLE IF NOT EXISTS `acc_fiscal_years` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_fy_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_accounts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `type` ENUM('asset','liability','equity','revenue','expense') NOT NULL,
  `sub_type` VARCHAR(50) NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `description` TEXT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `opening_balance_type` ENUM('debit','credit') NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_code` (`code`),
  INDEX `idx_type` (`type`),
  INDEX `idx_sub_type` (`sub_type`),
  INDEX `idx_parent` (`parent_id`),
  FOREIGN KEY (`parent_id`) REFERENCES `acc_accounts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_journal_entries` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `entry_number` VARCHAR(30) NOT NULL,
  `date` DATE NOT NULL,
  `fiscal_year_id` BIGINT UNSIGNED NOT NULL,
  `reference` VARCHAR(255) NULL,
  `narration` TEXT NULL,
  `source_type` ENUM('manual','sales_invoice','purchase_invoice','payment_voucher','receipt_voucher') NOT NULL DEFAULT 'manual',
  `source_id` BIGINT UNSIGNED NULL,
  `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `is_posted` TINYINT(1) NOT NULL DEFAULT 0,
  `is_reversed` TINYINT(1) NOT NULL DEFAULT 0,
  `reversed_by` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `posted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_entry_number` (`entry_number`),
  INDEX `idx_date` (`date`),
  INDEX `idx_fiscal_year` (`fiscal_year_id`),
  INDEX `idx_source` (`source_type`, `source_id`),
  INDEX `idx_posted` (`is_posted`),
  FOREIGN KEY (`fiscal_year_id`) REFERENCES `acc_fiscal_years`(`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_journal_entry_lines` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `journal_entry_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `debit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `credit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_journal` (`journal_entry_id`),
  INDEX `idx_account` (`account_id`),
  FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `acc_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_contacts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type` ENUM('vendor','other') NOT NULL DEFAULT 'vendor',
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `ntn` VARCHAR(50) NULL,
  `strn` VARCHAR(50) NULL,
  `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_type` (`type`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_sales_invoices` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(30) NOT NULL,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `due_date` DATE NULL,
  `reference` VARCHAR(255) NULL,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `terms` TEXT NULL,
  `journal_entry_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_invoice_number` (`invoice_number`),
  INDEX `idx_client` (`client_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_date` (`date`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`),
  FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_sales_invoice_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sales_invoice_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_invoice` (`sales_invoice_id`),
  FOREIGN KEY (`sales_invoice_id`) REFERENCES `acc_sales_invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `acc_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_purchase_invoices` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `bill_number` VARCHAR(30) NOT NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `vendor_name` VARCHAR(255) NULL,
  `vendor_invoice_no` VARCHAR(100) NULL,
  `date` DATE NOT NULL,
  `due_date` DATE NULL,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft','received','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `notes` TEXT NULL,
  `journal_entry_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_bill_number` (`bill_number`),
  INDEX `idx_contact` (`contact_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_date` (`date`),
  FOREIGN KEY (`contact_id`) REFERENCES `acc_contacts`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_purchase_invoice_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `purchase_invoice_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_invoice` (`purchase_invoice_id`),
  FOREIGN KEY (`purchase_invoice_id`) REFERENCES `acc_purchase_invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `acc_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_vouchers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `voucher_number` VARCHAR(30) NOT NULL,
  `type` ENUM('payment','receipt') NOT NULL,
  `date` DATE NOT NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `contact_id` BIGINT UNSIGNED NULL,
  `party_name` VARCHAR(255) NULL,
  `payment_account_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash','bank_transfer','cheque','online') NOT NULL DEFAULT 'cash',
  `cheque_number` VARCHAR(50) NULL,
  `reference` VARCHAR(255) NULL,
  `narration` TEXT NULL,
  `status` ENUM('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
  `invoice_id` BIGINT UNSIGNED NULL,
  `invoice_type` ENUM('sales','purchase') NULL,
  `journal_entry_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_voucher_number` (`voucher_number`),
  INDEX `idx_type` (`type`),
  INDEX `idx_date` (`date`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`contact_id`) REFERENCES `acc_contacts`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`payment_account_id`) REFERENCES `acc_accounts`(`id`),
  FOREIGN KEY (`journal_entry_id`) REFERENCES `acc_journal_entries`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_voucher_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `voucher_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_voucher` (`voucher_id`),
  FOREIGN KEY (`voucher_id`) REFERENCES `acc_vouchers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`account_id`) REFERENCES `acc_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `acc_settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

];

foreach ($tables as $sql) {
    $pdo->exec($sql);
    preg_match('/`(\w+)`/', $sql, $m);
    echo "Created: {$m[1]}\n";
}

// ── DEFAULT FISCAL YEAR ──
echo "\nSeeding fiscal year...\n";
$now = date('Y-m-d H:i:s');
$pdo->exec("INSERT IGNORE INTO acc_fiscal_years (name, start_date, end_date, is_active, created_at, updated_at) VALUES ('FY 2025-26', '2025-07-01', '2026-06-30', 1, '$now', '$now')");
echo "FY 2025-26 created.\n";

// ── DEFAULT CHART OF ACCOUNTS ──
echo "\nSeeding chart of accounts...\n";
$accounts = [
    // Assets
    ['1000','Current Assets','asset','current_asset',null,1],
    ['1100','Cash and Bank','asset','bank','1000',1],
    ['1110','Cash in Hand','asset','bank','1100',0],
    ['1120','Bank Account - HBL','asset','bank','1100',0],
    ['1130','Bank Account - Meezan','asset','bank','1100',0],
    ['1200','Accounts Receivable','asset','current_asset',null,1],
    ['1210','Advance Tax Paid (WHT Adjustable)','asset','current_asset',null,0],
    ['1220','Input Sales Tax Adjustable','asset','current_asset',null,0],
    ['1300','Prepaid Expenses','asset','current_asset',null,0],
    ['1310','Prepaid Rent','asset','current_asset','1300',0],
    ['1320','Security Deposits','asset','current_asset','1300',0],
    ['1500','Fixed Assets','asset','fixed_asset',null,1],
    ['1510','Office Furniture & Fixtures','asset','fixed_asset','1500',0],
    ['1520','Computer Equipment','asset','fixed_asset','1500',0],
    ['1530','Vehicles','asset','fixed_asset','1500',0],
    ['1540','Accumulated Depreciation - Furniture','asset','fixed_asset','1500',0],
    ['1550','Accumulated Depreciation - Equipment','asset','fixed_asset','1500',0],
    ['1560','Accumulated Depreciation - Vehicles','asset','fixed_asset','1500',0],
    // Liabilities
    ['2000','Current Liabilities','liability','current_liability',null,1],
    ['2100','Accounts Payable','liability','current_liability',null,1],
    ['2200','Salaries Payable','liability','current_liability',null,0],
    ['2300','Sales Tax Payable','liability','current_liability',null,0],
    ['2310','Withholding Tax Payable','liability','current_liability',null,0],
    ['2320','Income Tax Payable','liability','current_liability',null,0],
    ['2400','Accrued Liabilities','liability','current_liability',null,0],
    ['2410','Client Advance Payments','liability','current_liability',null,0],
    ['2500','Long Term Liabilities','liability','long_term_liability',null,1],
    ['2510','Long Term Loans','liability','long_term_liability','2500',0],
    // Equity
    ['3000','Owner\'s Equity','equity','equity',null,1],
    ['3100','Partner Capital - Partner A','equity','equity','3000',0],
    ['3200','Partner Capital - Partner B','equity','equity','3000',0],
    ['3300','Retained Earnings','equity','retained_earnings',null,1],
    ['3400','Drawings - Partner A','equity','equity','3000',0],
    ['3500','Drawings - Partner B','equity','equity','3000',0],
    // Revenue
    ['4000','Service Revenue','revenue','revenue',null,1],
    ['4100','Tax Consultation Fees','revenue','revenue','4000',0],
    ['4110','Income Tax Return Filing Fees','revenue','revenue','4000',0],
    ['4120','Sales Tax Return Filing Fees','revenue','revenue','4000',0],
    ['4130','KPRA Return Filing Fees','revenue','revenue','4000',0],
    ['4140','Company Incorporation Fees','revenue','revenue','4000',0],
    ['4150','NTN/STRN Registration Fees','revenue','revenue','4000',0],
    ['4160','FBR Notice Handling Fees','revenue','revenue','4000',0],
    ['4170','Audit & Assurance Fees','revenue','revenue','4000',0],
    ['4180','Bookkeeping Service Fees','revenue','revenue','4000',0],
    ['4190','SECP Compliance Fees','revenue','revenue','4000',0],
    ['4500','Other Income','revenue','other_income',null,1],
    ['4510','Interest Income','revenue','other_income','4500',0],
    ['4520','Miscellaneous Income','revenue','other_income','4500',0],
    // Expenses
    ['5000','Operating Expenses','expense','operating_expense',null,1],
    ['5100','Salaries & Wages','expense','operating_expense','5000',0],
    ['5110','Employee Benefits','expense','operating_expense','5000',0],
    ['5120','EOBI Contribution','expense','operating_expense','5000',0],
    ['5200','Rent Expense','expense','operating_expense','5000',0],
    ['5210','Utilities - Electricity','expense','operating_expense','5000',0],
    ['5220','Utilities - Internet & Telephone','expense','operating_expense','5000',0],
    ['5230','Utilities - Gas','expense','operating_expense','5000',0],
    ['5300','Office Supplies','expense','operating_expense','5000',0],
    ['5310','Printing & Stationery','expense','operating_expense','5000',0],
    ['5400','Software & Subscriptions','expense','operating_expense','5000',0],
    ['5500','Professional Development','expense','operating_expense','5000',0],
    ['5600','Travel & Transportation','expense','operating_expense','5000',0],
    ['5610','Fuel & Vehicle Maintenance','expense','operating_expense','5000',0],
    ['5700','Depreciation Expense','expense','operating_expense','5000',0],
    ['5800','Bank Charges','expense','operating_expense','5000',0],
    ['5810','Government Fees & Stamps','expense','operating_expense','5000',0],
    ['5900','Marketing & Advertising','expense','operating_expense','5000',0],
    ['5910','Client Entertainment','expense','operating_expense','5000',0],
    ['5920','Insurance','expense','operating_expense','5000',0],
    ['5950','Other Expenses','expense','other_expense',null,1],
    ['5960','Miscellaneous Expense','expense','other_expense','5950',0],
    ['5970','Penalties & Fines','expense','other_expense','5950',0],
];

// First pass: insert accounts without parent references
$codeToId = [];
foreach ($accounts as $a) {
    $check = $pdo->prepare("SELECT id FROM acc_accounts WHERE code = ?");
    $check->execute([$a[0]]);
    if ($check->fetch()) {
        $id = $pdo->query("SELECT id FROM acc_accounts WHERE code = '{$a[0]}'")->fetchColumn();
        $codeToId[$a[0]] = $id;
        continue;
    }
    $pdo->exec("INSERT INTO acc_accounts (code, name, type, sub_type, is_system, is_active, created_at, updated_at) VALUES (
        '{$a[0]}', " . $pdo->quote($a[1]) . ", '{$a[2]}', '{$a[3]}', {$a[5]}, 1, '$now', '$now')");
    $codeToId[$a[0]] = $pdo->lastInsertId();
}

// Second pass: set parent_id
foreach ($accounts as $a) {
    if ($a[4] && isset($codeToId[$a[4]])) {
        $parentId = $codeToId[$a[4]];
        $id = $codeToId[$a[0]];
        $pdo->exec("UPDATE acc_accounts SET parent_id = $parentId WHERE id = $id");
    }
}
echo count($accounts) . " accounts seeded.\n";

// ── DEFAULT SETTINGS ──
echo "\nSeeding settings...\n";
$settings = [
    ['invoice_prefix', 'INV'],
    ['bill_prefix', 'BILL'],
    ['payment_prefix', 'PV'],
    ['receipt_prefix', 'RV'],
    ['journal_prefix', 'JV'],
    ['default_receivable_account', $codeToId['1200'] ?? ''],
    ['default_payable_account', $codeToId['2100'] ?? ''],
    ['default_cash_account', $codeToId['1110'] ?? ''],
    ['default_bank_account', $codeToId['1120'] ?? ''],
    ['default_sales_account', $codeToId['4100'] ?? ''],
    ['default_purchase_account', $codeToId['5000'] ?? ''],
    ['default_sales_tax_account', $codeToId['2300'] ?? ''],
    ['company_name', 'FairTax International'],
    ['company_address', 'Peshawar, Pakistan'],
    ['company_ntn', ''],
    ['company_strn', ''],
    ['current_fiscal_year_id', '1'],
    ['invoice_terms', 'Payment due within 30 days'],
    ['invoice_footer', 'Thank you for your business!'],
];
foreach ($settings as $s) {
    $pdo->exec("INSERT IGNORE INTO acc_settings (`key`, `value`, created_at, updated_at) VALUES ('{$s[0]}', " . $pdo->quote($s[1]) . ", '$now', '$now')");
}
echo count($settings) . " settings seeded.\n";

echo "\n========================================\n";
echo "ACCOUNTING MODULE SETUP COMPLETE!\n";
echo "========================================\n";
echo "Tables: 12\nAccounts: " . count($accounts) . "\nSettings: " . count($settings) . "\n";
echo "</pre>";
