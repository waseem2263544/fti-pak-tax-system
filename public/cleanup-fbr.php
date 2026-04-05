<?php
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Delete spam "Format for Sub User DI CRM" notices
$stmt = $pdo->prepare("DELETE FROM notifications WHERE related_fbr_notice_id IN (SELECT id FROM fbr_notices WHERE subject LIKE '%Format for Sub User DI CRM%')");
$stmt->execute();
echo "Deleted related notifications.\n";

$stmt = $pdo->prepare("DELETE FROM fbr_notices WHERE subject LIKE '%Format for Sub User DI CRM%'");
$stmt->execute();
echo "Deleted " . $stmt->rowCount() . " spam FBR notices.\n";

$count = $pdo->query("SELECT COUNT(*) FROM fbr_notices")->fetchColumn();
echo "Remaining notices: $count\n</pre>";
