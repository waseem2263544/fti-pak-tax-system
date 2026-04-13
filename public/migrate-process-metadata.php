<?php
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("ALTER TABLE processes ADD COLUMN template VARCHAR(100) NULL AFTER notes");
    echo "Added template column.\n";
} catch (Exception $e) { echo "template: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE processes ADD COLUMN metadata JSON NULL AFTER template");
    echo "Added metadata column.\n";
} catch (Exception $e) { echo "metadata: " . $e->getMessage() . "\n"; }

echo "Done!\n</pre>";
