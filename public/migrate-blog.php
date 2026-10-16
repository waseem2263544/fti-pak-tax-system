<?php
/**
 * Blog posts, managed here and read by the website.
 *
 * The fields mirror the BlogPost interface the Next.js site already uses, so
 * the site's templates need no reworking - only their source changes, from a
 * TypeScript array to this table.
 *
 * `updated` is deliberately separate from `date`: on tax content it drives the
 * visible "last reviewed" line and schema.org dateModified, and bumping it
 * without a real revision would be a false trust signal.
 */
set_time_limit(180);
echo "<pre><h2>Blog setup</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `posts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `region` VARCHAR(4) NOT NULL DEFAULT 'pk',
  `slug` VARCHAR(200) NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `excerpt` TEXT NULL,
  `content` LONGTEXT NULL,
  `category` VARCHAR(80) NULL,
  `read_time` VARCHAR(30) NULL,
  `hero_image` VARCHAR(400) NULL,
  `hero_image_alt` VARCHAR(300) NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(12) NOT NULL DEFAULT 'draft',
  `published_on` DATE NULL,
  `reviewed_on` DATE NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `author_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_region_slug` (`region`, `slug`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created: posts\n";

echo "\nDone!\n</pre>";
