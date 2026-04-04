<?php
set_time_limit(300);
echo "<pre><h2>Import Missing Clients</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$clients = require __DIR__ . '/clients-data.php';
echo "Clients in data file: " . count($clients) . "\n\n";

$now = date('Y-m-d H:i:s');
$serviceMap = [];
$svc = $pdo->query("SELECT id, name, display_name FROM services");
while ($row = $svc->fetch(PDO::FETCH_ASSOC)) {
    $serviceMap[$row['display_name']] = $row['id'];
    $serviceMap[$row['name']] = $row['id'];
}

$validStatuses = ['Individual', 'AOP', 'Company'];
$imported = 0;
$skipped = 0;
$errors = 0;

foreach ($clients as $data) {
    $name = $data['Name'];

    $check = $pdo->prepare("SELECT id FROM clients WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) { $skipped++; continue; }

    $status = $data['Status'] ?? 'Individual';
    if (!in_array($status, $validStatuses)) $status = 'Individual';

    $email = $data['Email'] ?? '';
    $email = preg_replace('/\s*\/.*$/', '', $email);
    $email = preg_replace('/\s*pass.*$/i', '', $email);
    $email = trim($email);
    if ($email === '.' || $email === 'x' || stripos($email, 'Added in') !== false) $email = '';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';

    try {
        $pdo->exec("INSERT INTO clients (name, email, contact_no, status, folder_link, fbr_username, fbr_password, it_pin_code, secp_password, secp_pin, kpra_username, kpra_password, kpra_pin, notes, created_at, updated_at) VALUES (
            " . $pdo->quote($name) . ",
            " . $pdo->quote($email) . ",
            " . $pdo->quote($data['Contact No.'] ?? '') . ",
            " . $pdo->quote($status) . ",
            " . (!empty($data['Folder Link']) ? $pdo->quote($data['Folder Link']) : 'NULL') . ",
            " . (!empty($data['FBR Username']) ? $pdo->quote($data['FBR Username']) : 'NULL') . ",
            " . (!empty($data['FBR Password']) ? $pdo->quote($data['FBR Password']) : 'NULL') . ",
            " . (!empty($data['IT Pin Code']) ? $pdo->quote($data['IT Pin Code']) : 'NULL') . ",
            " . (!empty($data['SECP Password']) ? $pdo->quote($data['SECP Password']) : 'NULL') . ",
            " . (!empty($data['SECP Pin']) ? $pdo->quote($data['SECP Pin']) : 'NULL') . ",
            " . (!empty($data['KPRA Username']) ? $pdo->quote($data['KPRA Username']) : 'NULL') . ",
            " . (!empty($data['KPRA Password']) ? $pdo->quote($data['KPRA Password']) : 'NULL') . ",
            " . (!empty($data['KPRA Pin']) ? $pdo->quote($data['KPRA Pin']) : 'NULL') . ",
            " . ((!empty($data['Shareholders']) && $data['Shareholders'] !== '[]') ? $pdo->quote('Shareholders: ' . $data['Shareholders']) : 'NULL') . ",
            '$now', '$now')");

        $clientId = $pdo->lastInsertId();
        $imported++;
        echo "  OK: $name\n";

        $activeServices = $data['Active Services'] ?? '';
        if (!empty($activeServices) && $activeServices !== '[]') {
            $activeServices = str_replace(['[', ']', '"', '""'], '', $activeServices);
            $serviceNames = array_map('trim', explode(',', $activeServices));
            foreach ($serviceNames as $svcName) {
                if (empty($svcName) || $svcName === 'Not Active') continue;
                if (isset($serviceMap[$svcName])) {
                    try { $pdo->exec("INSERT IGNORE INTO client_services (client_id, service_id, created_at, updated_at) VALUES ($clientId, {$serviceMap[$svcName]}, '$now', '$now')"); } catch (Exception $e) {}
                }
            }
        }
    } catch (Exception $e) {
        echo "  ERROR: $name - " . $e->getMessage() . "\n";
        $errors++;
    }
}

$total = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
echo "\n========================================\n";
echo "IMPORT COMPLETE!\n";
echo "Imported: $imported\n";
echo "Skipped (existing): $skipped\n";
echo "Errors: $errors\n";
echo "Total clients in DB: $total\n";
echo "\nDelete import files for security!\n</pre>";
