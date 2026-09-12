<?php
/**
 * Movements against an asset.
 *
 * A plot bought last year and built on this year is one asset whose cost grew.
 * The statement shows the closing figure, but the working behind it is what
 * defends the number to an assessing officer: what was added, when, how much,
 * and why. Same for a partial disposal.
 *
 * Where a line has movements in a year, its figure is derived - last year's
 * closing plus additions less disposals - rather than typed.
 */
echo "<pre><h2>Asset movements</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `wealth_movements` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wealth_line_id` BIGINT UNSIGNED NOT NULL,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `kind` VARCHAR(10) NOT NULL DEFAULT 'addition',
  `occurred_on` DATE NULL,
  `note` VARCHAR(500) NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_line_year (`wealth_line_id`, `tax_year`),
  FOREIGN KEY (`wealth_line_id`) REFERENCES `wealth_lines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: wealth_movements\n";

echo "\nDone!\n</pre>";
