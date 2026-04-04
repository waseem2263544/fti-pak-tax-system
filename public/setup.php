<?php
// Direct SQL setup - no Laravel overhead, no timeout issues
set_time_limit(300);

echo "<h2>FTI Pak Database Setup</h2><pre>";

$host = 'localhost';
$db   = 'fairtax1_fti_pak';
$user = 'fairtax1_fti_pak';
$pass = 'Yousafzai1';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected\n\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

$tables = [

// 1. Users
"CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 2. Password reset tokens
"CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(255) PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 3. Sessions
"CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 4. Cache
"CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) PRIMARY KEY,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 5. Cache locks
"CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) PRIMARY KEY,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 6. Jobs
"CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  INDEX `idx_queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 7. Job batches
"CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 8. Failed jobs
"CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) NOT NULL UNIQUE,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 9. Roles
"CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `slug` VARCHAR(255) NULL,
  `display_name` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 10. Role-User pivot
"CREATE TABLE IF NOT EXISTS `role_user` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 11. Clients
"CREATE TABLE IF NOT EXISTS `clients` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `contact_no` VARCHAR(255) NOT NULL,
  `status` ENUM('Individual','AOP','Company') NOT NULL,
  `notes` TEXT NULL,
  `fbr_username` VARCHAR(255) NULL,
  `fbr_password` TEXT NULL,
  `it_pin_code` VARCHAR(255) NULL,
  `kpra_username` VARCHAR(255) NULL,
  `kpra_password` TEXT NULL,
  `kpra_pin` VARCHAR(255) NULL,
  `secp_password` TEXT NULL,
  `secp_pin` VARCHAR(255) NULL,
  `folder_link` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_name` (`name`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 12. Shareholders
"CREATE TABLE IF NOT EXISTS `shareholders` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `shareholder_client_id` BIGINT UNSIGNED NOT NULL,
  `share_percentage` DECIMAL(5,2) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_shareholder` (`client_id`, `shareholder_client_id`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shareholder_client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 13. Services
"CREATE TABLE IF NOT EXISTS `services` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `display_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `default_reminder_days` INT NOT NULL DEFAULT 7,
  `default_deadline_days` INT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 14. Client-Services pivot
"CREATE TABLE IF NOT EXISTS `client_services` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `next_deadline` DATE NULL,
  `reminder_days` INT NOT NULL DEFAULT 7,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_client_service` (`client_id`, `service_id`),
  INDEX `idx_next_deadline` (`next_deadline`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 15. Tasks
"CREATE TABLE IF NOT EXISTS `tasks` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('pending','in_progress','completed','overdue') NOT NULL DEFAULT 'pending',
  `due_date` DATE NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_due_date` (`due_date`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 16. Task-User pivot
"CREATE TABLE IF NOT EXISTS `task_user` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_task_user` (`task_id`, `user_id`),
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 17. FBR Notices
"CREATE TABLE IF NOT EXISTS `fbr_notices` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NULL,
  `email_message_id` VARCHAR(255) NOT NULL UNIQUE,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NULL,
  `notice_section` TEXT NULL,
  `tax_year` VARCHAR(255) NULL,
  `notice_date` DATE NULL,
  `email_received_at` DATE NOT NULL,
  `status` ENUM('new','reviewed','resolved','escalated') NOT NULL DEFAULT 'new',
  `sender_email` VARCHAR(255) NOT NULL,
  `raw_content` TEXT NULL,
  `is_escalated` TINYINT(1) NOT NULL DEFAULT 0,
  `escalated_to` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_client_id` (`client_id`),
  INDEX `idx_tax_year` (`tax_year`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`escalated_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 18. Reminders
"CREATE TABLE IF NOT EXISTS `reminders` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `deadline_date` DATE NOT NULL,
  `reminder_type` ENUM('7_days','3_days','1_day','overdue') NOT NULL DEFAULT '7_days',
  `email_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `in_app_notified` TINYINT(1) NOT NULL DEFAULT 0,
  `escalated` TINYINT(1) NOT NULL DEFAULT 0,
  `escalated_to` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_deadline_date` (`deadline_date`),
  INDEX `idx_reminder_type` (`reminder_type`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`escalated_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 19. Notifications
"CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `client_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('reminder','fbr_notice','task','escalation','general') NOT NULL DEFAULT 'general',
  `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `related_fbr_notice_id` BIGINT UNSIGNED NULL,
  `related_reminder_id` BIGINT UNSIGNED NULL,
  `related_task_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_type` (`type`),
  INDEX `idx_priority` (`priority`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`related_fbr_notice_id`) REFERENCES `fbr_notices`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`related_reminder_id`) REFERENCES `reminders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`related_task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 20. Microsoft Email Settings
"CREATE TABLE IF NOT EXISTS `microsoft_email_settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `client_id` VARCHAR(255) NOT NULL,
  `client_secret` VARCHAR(255) NOT NULL,
  `access_token` VARCHAR(255) NOT NULL,
  `refresh_token` VARCHAR(255) NOT NULL,
  `token_expires_at` TIMESTAMP NULL,
  `email_address` VARCHAR(255) NOT NULL,
  `fbr_sender_email` VARCHAR(255) NOT NULL DEFAULT 'no-reply@fbr.gov.pk',
  `last_synced_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

// 21. Migrations table (so Laravel knows migrations ran)
"CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

];

$count = 0;
foreach ($tables as $sql) {
    try {
        $pdo->exec($sql);
        $count++;
        // Extract table name for display
        preg_match('/`(\w+)`/', $sql, $m);
        echo "✓ Table: {$m[1]}\n";
    } catch (PDOException $e) {
        preg_match('/`(\w+)`/', $sql, $m);
        echo "✗ Table {$m[1]}: " . $e->getMessage() . "\n";
    }
}

echo "\n$count tables created.\n\n";

// Insert migration records
echo "Recording migrations...\n";
$migrations = [
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
    '2026_04_02_000001_create_roles_table',
    '2026_04_02_000002_create_role_user_table',
    '2026_04_02_000003_create_clients_table',
    '2026_04_02_000004_create_shareholders_table',
    '2026_04_02_000005_create_services_table',
    '2026_04_02_000006_create_client_services_table',
    '2026_04_02_000007_create_tasks_table',
    '2026_04_02_000008_create_task_user_table',
    '2026_04_02_000009_create_fbr_notices_table',
    '2026_04_02_000010_create_reminders_table',
    '2026_04_02_000011_create_notifications_table',
    '2026_04_02_000012_create_microsoft_email_settings_table',
];
foreach ($migrations as $m) {
    $pdo->exec("INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES ('$m', 1)");
}
echo "✓ Migrations recorded\n\n";

// Seed roles
echo "Seeding roles...\n";
$now = date('Y-m-d H:i:s');
$roles = [
    ['admin', 'admin', 'Administrator', 'Full system access'],
    ['consultant', 'consultant', 'Tax Consultant', 'Consultant with client and task management access'],
    ['staff', 'staff', 'Staff Member', 'Staff member with limited access'],
];
foreach ($roles as $r) {
    $pdo->exec("INSERT IGNORE INTO `roles` (`name`, `slug`, `display_name`, `description`, `created_at`, `updated_at`) VALUES ('$r[0]', '$r[1]', '$r[2]', '$r[3]', '$now', '$now')");
}
echo "✓ Roles seeded\n";

// Seed services
echo "Seeding services...\n";
$services = [
    ['income_tax_return', 'Income Tax Return', 'Annual income tax return filing', 30, 365],
    ['sales_tax_return', 'Sales Tax Return', 'Monthly/Quarterly sales tax return', 7, 30],
    ['kpra_return', 'KPRA Return', 'KPRA return filing', 15, 90],
    ['bookkeeping', 'Bookkeeping', 'Monthly bookkeeping and accounting services', 3, 30],
    ['withholding_tax_statement', 'Withholding Tax Statements', 'Withholding tax certificate generation and filing', 7, 60],
];
foreach ($services as $s) {
    $pdo->exec("INSERT IGNORE INTO `services` (`name`, `display_name`, `description`, `default_reminder_days`, `default_deadline_days`, `created_at`, `updated_at`) VALUES ('$s[0]', '$s[1]', '$s[2]', $s[3], $s[4], '$now', '$now')");
}
echo "✓ Services seeded\n\n";

// Create admin user
echo "Creating admin user...\n";
$adminEmail = 'admin@fairtaxint.com';
$check = $pdo->query("SELECT id FROM users WHERE email = '$adminEmail'")->fetch();
if (!$check) {
    $hashedPass = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO `users` (`name`, `email`, `password`, `created_at`, `updated_at`) VALUES ('Admin', '$adminEmail', '$hashedPass', '$now', '$now')");
    $userId = $pdo->lastInsertId();
    $adminRoleId = $pdo->query("SELECT id FROM roles WHERE slug = 'admin'")->fetchColumn();
    if ($adminRoleId) {
        $pdo->exec("INSERT INTO `role_user` (`user_id`, `role_id`, `created_at`, `updated_at`) VALUES ($userId, $adminRoleId, '$now', '$now')");
    }
    echo "✓ Admin created\n";
    echo "  Email: admin@fairtaxint.com\n";
    echo "  Password: admin123\n";
} else {
    echo "✓ Admin already exists\n";
}

echo "\n\n========================================\n";
echo "✅ SETUP COMPLETE!\n";
echo "========================================\n";
echo "\nLogin at: https://app.fairtaxint.com\n";
echo "Email: admin@fairtaxint.com\n";
echo "Password: admin123\n";
echo "\n⚠️  DELETE this file (setup.php) for security!\n";
echo "</pre>";
