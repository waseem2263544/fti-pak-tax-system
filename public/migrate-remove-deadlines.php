<?php
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("ALTER TABLE client_services DROP COLUMN next_deadline");
    echo "Dropped next_deadline from client_services.\n";
} catch (Exception $e) {
    echo "next_deadline: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE client_services DROP COLUMN reminder_days");
    echo "Dropped reminder_days from client_services.\n";
} catch (Exception $e) {
    echo "reminder_days: " . $e->getMessage() . "\n";
}

echo "Done!\n</pre>";
