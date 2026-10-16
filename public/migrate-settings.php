<?php
/**
 * A general settings table.
 *
 * Values that used to live in the .env - which meant a terminal, a one-shot
 * script and a deploy every time one changed - belong here instead, where
 * they can be edited in the app by someone with the right to.
 */
set_time_limit(180);
echo "<pre><h2>Settings table</h2>\n";

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

$pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(120) NOT NULL,
  `value` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "Ready: settings\n\nDone!\n</pre>";
