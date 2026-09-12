<?php
/**
 * Salary working.
 *
 * Salary is not one figure. It has an income side and a deduction side, each
 * built from heads the preparer adds, and it may be worked annually or month
 * by month. So it gets its own tables rather than sitting in income_items.
 *
 * Two heads are created with every working and cannot be deleted: Basic salary
 * on the income side, and Income tax deducted on the deduction side. Those are
 * the two that always exist and that other parts of the return depend on.
 */
set_time_limit(120);
echo "<pre><h2>Salary working setup</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `salary_workings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `tax_year` SMALLINT UNSIGNED NOT NULL,
  `employer` VARCHAR(255) NOT NULL,
  `basis` VARCHAR(10) NOT NULL DEFAULT 'annual',
  `notes` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_client_year (`client_id`, `tax_year`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: salary_workings\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS `salary_components` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `salary_working_id` BIGINT UNSIGNED NOT NULL,
  `side` VARCHAR(10) NOT NULL,
  `label` VARCHAR(200) NOT NULL,
  `treatment` VARCHAR(10) NULL,
  `is_tax` TINYINT(1) NOT NULL DEFAULT 0,
  `locked` TINYINT(1) NOT NULL DEFAULT 0,
  `annual_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX idx_working_side (`salary_working_id`, `side`),
  FOREIGN KEY (`salary_working_id`) REFERENCES `salary_workings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: salary_components\n";

/* Month is the calendar month, 7 (July) through 6 (June) in tax-year order. */
$pdo->exec("CREATE TABLE IF NOT EXISTS `salary_months` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `salary_component_id` BIGINT UNSIGNED NOT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
  UNIQUE KEY `uk_component_month` (`salary_component_id`, `month`),
  FOREIGN KEY (`salary_component_id`) REFERENCES `salary_components`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: salary_months\n";

echo "\nDone!\n</pre>";
