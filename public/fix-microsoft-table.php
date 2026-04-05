<?php
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("ALTER TABLE microsoft_email_settings MODIFY client_secret TEXT NOT NULL");
$pdo->exec("ALTER TABLE microsoft_email_settings MODIFY access_token TEXT NOT NULL");
$pdo->exec("ALTER TABLE microsoft_email_settings MODIFY refresh_token TEXT NOT NULL");
echo "Done! Columns updated to TEXT.\n</pre>";
