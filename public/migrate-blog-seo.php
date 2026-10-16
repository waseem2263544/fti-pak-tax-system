<?php
/**
 * SEO fields for blog posts.
 *
 * The scores are stored only so the post list can show at a glance which
 * posts need work. They are recomputed in the browser on every edit, so the
 * stored figure is a cache of the last save, never the source of truth.
 *
 * Credentials come from the .env rather than being written in here, unlike
 * the older migrate scripts: that file is not in the repository, and a
 * password in a file that is only makes itself harder to change later.
 */
set_time_limit(180);
echo "<pre><h2>Blog SEO fields</h2>\n";

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

$columns = [
    'focus_keyphrase'     => "VARCHAR(200) NULL AFTER `hero_image_alt`",
    'related_keyphrases'  => "VARCHAR(500) NULL AFTER `focus_keyphrase`",
    'meta_title'          => "VARCHAR(300) NULL AFTER `related_keyphrases`",
    'meta_description'    => "VARCHAR(400) NULL AFTER `meta_title`",
    'canonical_url'       => "VARCHAR(400) NULL AFTER `meta_description`",
    'noindex'             => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `canonical_url`",
    'seo_score'           => "TINYINT UNSIGNED NULL AFTER `noindex`",
    'readability_score'   => "TINYINT UNSIGNED NULL AFTER `seo_score`",
];

$have = [];
foreach ($pdo->query("SHOW COLUMNS FROM `posts`") as $row) {
    $have[$row['Field']] = true;
}

foreach ($columns as $name => $definition) {
    if (isset($have[$name])) {
        echo "Already there: $name\n";
        continue;
    }
    $pdo->exec("ALTER TABLE `posts` ADD COLUMN `$name` $definition");
    echo "Added: $name\n";
}

echo "\nDone!\n</pre>";
