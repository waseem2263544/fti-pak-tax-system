<?php
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `file_numbers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `file_no` INT UNSIGNED NOT NULL,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_file_no (`file_no`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: file_numbers\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS `letter_numbers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `date` DATE NOT NULL,
  `reference` VARCHAR(255) NOT NULL,
  `sequence_no` INT UNSIGNED NOT NULL,
  `year` INT UNSIGNED NOT NULL,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_reference (`reference`),
  INDEX idx_year (`year`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: letter_numbers\n";

echo "\nDone!\n</pre>";
