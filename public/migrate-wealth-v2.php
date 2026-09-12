<?php
/**
 * Rework the wealth tables onto FBR's own heads and codes.
 *
 * The first cut grouped assets under the firm's working labels. The form asks
 * for specific attributes per head - a vehicle needs its E&TD registration, a
 * plot its Mauza and area - so each line now carries an IRIS code and a bag of
 * details belonging to that head.
 *
 * Income moves from seven totals to itemised workings: a capital gain computed
 * per disposal, property income per property, salary per employer.
 */
set_time_limit(180);
echo "<pre><h2>Wealth Statement — FBR heads</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$has = function (string $t, string $c) use ($pdo): bool {
    return (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = '{$t}' AND column_name = '{$c}'")->fetchColumn();
};
$table = function (string $t) use ($pdo): bool {
    return (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = '{$t}'")->fetchColumn();
};

if (!$table('wealth_lines')) { exit("Run migrate-wealth.php first.\n</pre>"); }

foreach (['code' => "VARCHAR(8) NULL AFTER `kind`", 'details' => "JSON NULL AFTER `description`"] as $col => $ddl) {
    if (!$has('wealth_lines', $col)) {
        $pdo->exec("ALTER TABLE `wealth_lines` ADD COLUMN `{$col}` {$ddl}");
        echo "Added wealth_lines.{$col}\n";
    }
}

// Carry the first cut's working sections onto the form's codes, so nothing
// already entered has to be retyped.
$map = [
    'agricultural_property'    => '7001',
    'immovable_property'       => '7002',
    'business_capital'         => '7003',
    'financial_assets'         => '7006',
    'bank_accounts'            => '7006',
    'receivables'              => '7007',
    'motor_vehicles'           => '7008',
    'household_effects'        => '7010',
    'cash_in_hand'             => '7012',
    'other_assets'             => '7013',
    'assets_in_others_name'    => '7014',
    'foreign_immovable'        => '7016',
    'foreign_business_capital' => '7016',
    'creditors'                => '7021',
    'other_liabilities'        => '7021',
];
$stmt = $pdo->prepare("UPDATE wealth_lines SET code = ? WHERE section = ? AND code IS NULL");
$moved = 0;
foreach ($map as $section => $code) {
    $stmt->execute([$code, $section]);
    $moved += $stmt->rowCount();
}
echo "Mapped {$moved} existing lines onto FBR codes.\n";

// Anything unmapped falls to "Any other asset" rather than vanishing from the
// statement.
$pdo->exec("UPDATE wealth_lines SET code = '7013' WHERE code IS NULL AND kind = 'asset'");
$pdo->exec("UPDATE wealth_lines SET code = '7021' WHERE code IS NULL AND kind = 'liability'");

$pdo->exec("CREATE TABLE IF NOT EXISTS `income_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `head` VARCHAR(30) NOT NULL,
  `description` VARCHAR(400) NULL,
  `wealth_line_id` BIGINT UNSIGNED NULL,
  `details` JSON NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `treatment` VARCHAR(12) NOT NULL DEFAULT 'taxable',
  `tax_deducted` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_client_year_head (`client_id`, `tax_year`, `head`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`wealth_line_id`) REFERENCES `wealth_lines`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: income_items\n";

// Annex-F, and the reconciliation codes the first cut did not carry.
$pdo->exec("CREATE TABLE IF NOT EXISTS `wealth_expenses` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `code` VARCHAR(8) NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_client_year_code` (`client_id`, `tax_year`, `code`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: wealth_expenses (Annex-F)\n";

foreach ([
    'adjustments'  => "DECIMAL(18,2) NOT NULL DEFAULT 0",
    'inheritance'  => "DECIMAL(18,2) NOT NULL DEFAULT 0",
    'gain_disposal'=> "DECIMAL(18,2) NOT NULL DEFAULT 0",
    'gift_given'   => "DECIMAL(18,2) NOT NULL DEFAULT 0",
    'loss_disposal'=> "DECIMAL(18,2) NOT NULL DEFAULT 0",
] as $col => $ddl) {
    if (!$has('wealth_reconciliations', $col)) {
        $pdo->exec("ALTER TABLE `wealth_reconciliations` ADD COLUMN `{$col}` {$ddl}");
        echo "Added wealth_reconciliations.{$col}\n";
    }
}

echo "\nDone!\n</pre>";
