<?php
/**
 * Social Studio tables.
 *
 * social_posts holds every TikTok post Claude drafts, from generation through
 * review and approval to the rendered files. social_ideas is the bank of
 * topics the daily run and the "new post" form draw on.
 *
 * Safe to run more than once: every statement is IF NOT EXISTS.
 * Open /migrate-social.php once after deploying, then it can stay.
 */
set_time_limit(180);
echo "<pre><h2>Social Studio tables</h2>\n";

$env = [];
foreach (file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \t\"'");
}

$pdo = new PDO(
    "mysql:host=" . ($env['DB_HOST'] ?? 'localhost') . ";dbname=" . $env['DB_DATABASE'] . ";charset=utf8mb4",
    $env['DB_USERNAME'],
    $env['DB_PASSWORD']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `format` VARCHAR(20) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'generating',
  `topic` VARCHAR(500) NOT NULL,
  `audience` VARCHAR(200) NULL,
  `brief` TEXT NULL,
  `title` VARCHAR(300) NULL,
  `caption` TEXT NULL,
  `hashtags` JSON NULL,
  `slides` JSON NULL,
  `fact_checks` JSON NULL,
  `feedback` TEXT NULL,
  `revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `assets` JSON NULL,
  `video_path` VARCHAR(500) NULL,
  `render_status` VARCHAR(20) NULL,
  `render_error` TEXT NULL,
  `render_claimed_at` TIMESTAMP NULL,
  `error` TEXT NULL,
  `ai_model` VARCHAR(80) NULL,
  `ai_usage` JSON NULL,
  `idea_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `planned_for` DATE NULL,
  `posted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `social_posts_status_index` (`status`),
  INDEX `social_posts_render_status_index` (`render_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "OK   social_posts\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS `social_ideas` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `topic` VARCHAR(500) NOT NULL,
  `angle` TEXT NULL,
  `audience` VARCHAR(200) NULL,
  `format` VARCHAR(20) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'new',
  `source` VARCHAR(20) NOT NULL DEFAULT 'manual',
  `post_id` BIGINT UNSIGNED NULL,
  `used_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `social_ideas_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "OK   social_ideas\n";

// Rendered files go to the app's local disk (storage/app/private/social); Laravel creates the folders.


echo "\nDone. Open Social Studio > Settings to add the Claude API key.</pre>";
