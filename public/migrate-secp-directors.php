<?php
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS `secp_directors` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `director_name` VARCHAR(255) NOT NULL,
  `cnic` VARCHAR(50) NULL,
  `secp_password` TEXT NULL,
  `secp_pin` VARCHAR(50) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_client` (`client_id`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Created secp_directors table.\n";

// Migrate existing SECP data from clients to secp_directors
$clients = $pdo->query("SELECT id, name, secp_password, secp_pin FROM clients WHERE secp_password IS NOT NULL AND secp_password != '' OR secp_pin IS NOT NULL AND secp_pin != ''");
$migrated = 0;
$now = date('Y-m-d H:i:s');
while ($client = $clients->fetch(PDO::FETCH_ASSOC)) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM secp_directors WHERE client_id = ?");
    $check->execute([$client['id']]);
    if ($check->fetchColumn() > 0) continue;

    $stmt = $pdo->prepare("INSERT INTO secp_directors (client_id, director_name, secp_password, secp_pin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client['id'], 'Director 1', $client['secp_password'], $client['secp_pin'], $now, $now]);
    $migrated++;
}
echo "Migrated $migrated existing SECP records.\n";
echo "Done!\n</pre>";
