<?php
set_time_limit(60);
echo "<pre>";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = [
"CREATE TABLE IF NOT EXISTS `processes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `assigned_to` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `stage` ENUM('intake','in_progress','review','completed') NOT NULL DEFAULT 'intake',
  `start_date` DATE NULL,
  `due_date` DATE NULL,
  `completed_date` DATE NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_stage (`stage`),
  INDEX idx_due_date (`due_date`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `proceedings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `stage` ENUM('department','commissioner_appeals','tribunal') NOT NULL DEFAULT 'department',
  `case_number` VARCHAR(255) NULL,
  `tax_year` VARCHAR(255) NULL,
  `section` VARCHAR(255) NULL,
  `hearing_date` DATE NULL,
  `order_date` DATE NULL,
  `status` ENUM('pending','adjourned','decided','appealed') NOT NULL DEFAULT 'pending',
  `outcome` TEXT NULL,
  `notes` TEXT NULL,
  `assigned_to` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_stage (`stage`),
  INDEX idx_status (`status`),
  INDEX idx_hearing (`hearing_date`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `automated_tasks` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `trigger_type` ENUM('deadline_based','date_based','recurring','event_based') NOT NULL DEFAULT 'recurring',
  `trigger_value` VARCHAR(255) NULL,
  `service_id` BIGINT UNSIGNED NULL,
  `task_template` VARCHAR(255) NOT NULL,
  `priority` INT NOT NULL DEFAULT 1,
  `assign_to_roles` JSON NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_run_at` TIMESTAMP NULL,
  `next_run_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($tables as $sql) {
    $pdo->exec($sql);
    preg_match('/`(\w+)`/', $sql, $m);
    echo "Created: {$m[1]}\n";
}

echo "\nDone! New tables created.\n</pre>";
