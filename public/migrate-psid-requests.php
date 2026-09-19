<?php
/**
 * A PSID request: a selection of entries sent to IRIS, waiting for its number.
 *
 * Entries carry psid_no directly, which is fine once a PSID exists but leaves
 * nowhere to record that a batch is out being created. Without that there is
 * nothing to stop the same entries being sent twice - the duplication the firm
 * already hits when a client sends two overlapping files.
 */
echo "<pre><h2>PSID requests</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `wht_psid_requests` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `token` CHAR(32) NOT NULL,
  `wht_company_id` BIGINT UNSIGNED NOT NULL,
  `kind` VARCHAR(12) NOT NULL,
  `entry_ids` JSON NOT NULL,
  `entry_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_tax` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(12) NOT NULL DEFAULT 'open',
  `psid_no` VARCHAR(50) NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_token` (`token`),
  INDEX idx_company_status (`wht_company_id`, `status`),
  FOREIGN KEY (`wht_company_id`) REFERENCES `wht_companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: wht_psid_requests\n";

echo "\nDone!\n</pre>";
