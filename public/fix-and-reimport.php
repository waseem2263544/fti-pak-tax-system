<?php
set_time_limit(300);
echo "<pre><h2>Debug & Import Missing Clients</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all existing client names
$existing = [];
$res = $pdo->query("SELECT name FROM clients");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $existing[] = $row['name'];
}
echo "Clients in DB: " . count($existing) . "\n\n";

// Read CSV
$csvFile = __DIR__ . '/pakistan-clients.csv';
$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle);
$header = array_map(function($h) { return trim($h, " \t\n\r\0\x0B\"'"); }, $header);

$csvNames = [];
$csvData = [];
while (($row = fgetcsv($handle)) !== false) {
    $data = [];
    foreach ($header as $i => $col) { $data[$col] = isset($row[$i]) ? trim($row[$i]) : ''; }
    $name = $data['Name'] ?? '';
    if (empty($name)) continue;
    $csvNames[] = $name;
    $csvData[] = $data;
}
fclose($handle);

echo "Clients in CSV: " . count($csvNames) . "\n\n";

// Find missing
$missing = [];
foreach ($csvNames as $i => $name) {
    if (!in_array($name, $existing)) {
        $missing[] = $csvData[$i];
    }
}

echo "Missing from DB: " . count($missing) . "\n\n";

if (count($missing) === 0) {
    echo "All clients already in DB!\n";
    // Show first 5 from DB and CSV to compare
    echo "\nFirst 5 DB names:\n";
    for ($i = 0; $i < min(5, count($existing)); $i++) {
        echo "  DB: [" . bin2hex($existing[$i]) . "] = " . $existing[$i] . "\n";
    }
    echo "\nFirst 5 CSV names:\n";
    for ($i = 0; $i < min(5, count($csvNames)); $i++) {
        echo "  CSV: [" . bin2hex($csvNames[$i]) . "] = " . $csvNames[$i] . "\n";
    }
    echo "\nCSV name 'Hussan Ara Hoti' in DB? " . (in_array('Hussan Ara Hoti', $existing) ? 'YES' : 'NO') . "\n";

    // Check with LIKE query
    $stmt = $pdo->prepare("SELECT id, name, HEX(name) as hex_name FROM clients WHERE name LIKE ?");
    $stmt->execute(['%Hussan%']);
    $found = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "DB search for 'Hussan': " . count($found) . " results\n";
    foreach ($found as $f) echo "  id={$f['id']} name={$f['name']} hex={$f['hex_name']}\n";

    echo "</pre>";
    exit;
}

// Import missing
echo "Importing " . count($missing) . " missing clients...\n\n";

$now = date('Y-m-d H:i:s');
$serviceMap = [];
$svc = $pdo->query("SELECT id, name, display_name FROM services");
while ($row = $svc->fetch(PDO::FETCH_ASSOC)) {
    $serviceMap[$row['display_name']] = $row['id'];
    $serviceMap[$row['name']] = $row['id'];
}
$validStatuses = ['Individual', 'AOP', 'Company'];
$imported = 0;
$errors = 0;

foreach ($missing as $data) {
    $name = $data['Name'];
    $status = $data['Status'] ?? 'Individual';
    if (!in_array($status, $validStatuses)) $status = 'Individual';

    $email = $data['Email'] ?? '';
    $email = preg_replace('/\s*\/.*$/', '', $email);
    $email = preg_replace('/\s*pass.*$/i', '', $email);
    $email = trim($email);
    if ($email === '.' || $email === 'x' || $email === 'Added in Fair Tax International') $email = '';
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
echo "Imported: $imported | Errors: $errors | Total in DB: $total\n";
echo "</pre>";
