<?php
/**
 * Wealth statement and income tax working papers.
 *
 * Modelled on how the firm already works in Excel: a comparative statement
 * where each asset is a row and each tax year a column. So an asset is a
 * lasting thing belonging to a client (wealth_lines) and its yearly figure is
 * a separate row (wealth_values). Matching assets across years by description,
 * which is what the spreadsheet does implicitly, would break the moment a
 * label was retyped.
 *
 * No tax is computed anywhere here. Tax chargeable is entered by hand.
 */
set_time_limit(120);
echo "<pre><h2>Wealth Statement setup</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `wealth_lines` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `kind` VARCHAR(12) NOT NULL DEFAULT 'asset',
  `section` VARCHAR(40) NOT NULL,
  `description` VARCHAR(400) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `disposed_in` SMALLINT UNSIGNED NULL,
  `notes` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_client_section (`client_id`, `section`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: wealth_lines\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS `wealth_values` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wealth_line_id` BIGINT UNSIGNED NOT NULL,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_line_year` (`wealth_line_id`, `tax_year`),
  FOREIGN KEY (`wealth_line_id`) REFERENCES `wealth_lines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: wealth_values\n";

/*
 * The reconciliation. Every figure here is entered; the only arithmetic the
 * app does is adding them up and showing whether sources cover what the
 * increase in wealth requires.
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS `wealth_reconciliations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `opening_wealth` DECIMAL(18,2) NULL,
  `personal_expenses` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `gifts_family` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `other_outflows` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `income_normal` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `income_exempt` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `income_ftr` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `foreign_remittance` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `gift_received` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `other_sources` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_client_year` (`client_id`, `tax_year`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: wealth_reconciliations\n";

/*
 * The income working. Heads of income are recorded as declared; tax_chargeable
 * is typed in, deliberately, so the app never puts a figure on a return that
 * nobody checked.
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS `income_workings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `salary` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `property` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `business` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `capital_gain` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `other_sources` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `foreign_sources` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `agriculture` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `exempt_income` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `ftr_income` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `deductible_allowances` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `tax_chargeable` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `tax_reductions_credits` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `tax_deducted` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `tax_paid` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_client_year` (`client_id`, `tax_year`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: income_workings\n";

echo "\nDone!\n</pre>";
