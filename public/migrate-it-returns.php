<?php
set_time_limit(120);
echo "<pre><h2>Income Tax Return Tracker Setup</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `it_return_trackers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'not_yet_contacted',
  `assigned_to` BIGINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_client` (`client_id`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "Created: it_return_trackers\n";

// Idempotent: add assigned_to to an already-existing table
$hasAssigned = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'it_return_trackers' AND column_name = 'assigned_to'")->fetchColumn();
if (!$hasAssigned) {
    $pdo->exec("ALTER TABLE `it_return_trackers` ADD COLUMN `assigned_to` BIGINT UNSIGNED NULL AFTER `status`");
    echo "Added assigned_to column.\n";
}
echo "\nDONE!\n";
echo "</pre>";
