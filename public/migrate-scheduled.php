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

echo "Done!\n</pre>";
