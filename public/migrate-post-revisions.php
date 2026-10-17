<?php
/**
 * Published versions of blog posts.
 *
 * Until now a post had one copy. Saving an edit to a live post changed what
 * the website served at once, and "save as draft" took the post off the site
 * altogether - neither of which is what anyone means by working on a draft.
 *
 * A row here is a version that was published. The website is served the
 * latest one; the post row itself is the copy being worked on. That also
 * gives a way back: the live version can be restored over the draft.
 */
set_time_limit(180);
echo "<pre><h2>Post revisions</h2>\n";

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

$pdo->exec("CREATE TABLE IF NOT EXISTS `post_revisions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `excerpt` TEXT NULL,
  `content` LONGTEXT NULL,
  `category` VARCHAR(80) NULL,
  `read_time` VARCHAR(30) NULL,
  `hero_image` VARCHAR(400) NULL,
  `hero_image_alt` VARCHAR(300) NULL,
  `focus_keyphrase` VARCHAR(200) NULL,
  `meta_title` VARCHAR(300) NULL,
  `meta_description` VARCHAR(400) NULL,
  `canonical_url` VARCHAR(400) NULL,
  `noindex` TINYINT(1) NOT NULL DEFAULT 0,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `published_on` DATE NULL,
  `reviewed_on` DATE NULL,
  `seo_score` TINYINT UNSIGNED NULL,
  `readability_score` TINYINT UNSIGNED NULL,
  `published_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_post` (`post_id`, `id`),
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Ready: post_revisions\n";

/*
 * Posts that were already live have no recorded version. What they show on
 * the website is exactly their own row, so that is what is written here -
 * otherwise their first draft edit would have nothing to fall back to.
 */
$existing = (int) $pdo->query("SELECT COUNT(*) FROM `post_revisions`")->fetchColumn();

if ($existing === 0) {
    $pdo->exec("INSERT INTO `post_revisions`
        (`post_id`, `slug`, `title`, `excerpt`, `content`, `category`, `read_time`, `hero_image`, `hero_image_alt`,
         `focus_keyphrase`, `meta_title`, `meta_description`, `canonical_url`, `noindex`, `featured`,
         `published_on`, `reviewed_on`, `seo_score`, `readability_score`, `created_at`, `updated_at`)
        SELECT `id`, `slug`, `title`, `excerpt`, `content`, `category`, `read_time`, `hero_image`, `hero_image_alt`,
               `focus_keyphrase`, `meta_title`, `meta_description`, `canonical_url`, `noindex`, `featured`,
               `published_on`, `reviewed_on`, `seo_score`, `readability_score`, NOW(), NOW()
        FROM `posts` WHERE `status` = 'published'");

    echo "Recorded the live version of " . $pdo->query("SELECT COUNT(*) FROM `post_revisions`")->fetchColumn() . " published posts.\n";
} else {
    echo "Revisions already present - left alone.\n";
}

echo "\nDone!\n</pre>";
