<?php
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Add assign_to_user column if not exists
try {
    $pdo->exec("ALTER TABLE automated_tasks ADD COLUMN assign_to_user BIGINT UNSIGNED NULL AFTER priority");
    echo "Added assign_to_user column.\n";
} catch (Exception $e) {
    echo "assign_to_user column already exists or error: " . $e->getMessage() . "\n";
}

// Modify trigger_type enum to include new types
$pdo->exec("ALTER TABLE automated_tasks MODIFY trigger_type ENUM('monthly','yearly','weekly','daily','deadline_based','date_based','recurring','event_based') NOT NULL DEFAULT 'monthly'");
echo "Updated trigger_type enum.\n";

try {
    $pdo->exec("ALTER TABLE automated_tasks ADD COLUMN run_at_time VARCHAR(10) DEFAULT '08:00' AFTER assign_to_user");
    echo "Added run_at_time column.\n";
} catch (Exception $e) {
    echo "run_at_time: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE automated_tasks ADD COLUMN due_in_days INT UNSIGNED DEFAULT 15 AFTER run_at_time");
    echo "Added due_in_days column.\n";
} catch (Exception $e) {
    echo "due_in_days: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE automated_tasks ADD COLUMN run_months JSON NULL AFTER due_in_days");
    echo "Added run_months column.\n";
} catch (Exception $e) {
    echo "run_months: " . $e->getMessage() . "\n";
}

$pdo->exec("ALTER TABLE automated_tasks MODIFY trigger_type ENUM('monthly','quarterly','yearly','weekly','daily','deadline_based','date_based','recurring','event_based') NOT NULL DEFAULT 'monthly'");
echo "Updated trigger_type enum with quarterly.\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `commentable_type` VARCHAR(255) NOT NULL,
  `commentable_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_commentable (`commentable_type`, `commentable_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created comments table.\n";

echo "Done!\n</pre>";
