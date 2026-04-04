<?php
set_time_limit(300);
echo "<pre><h2>Fix DB & Import Remaining Clients</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fix: allow email to be empty string
echo "Fixing email column to allow empty values...\n";
$pdo->exec("ALTER TABLE clients MODIFY COLUMN email VARCHAR(255) NOT NULL DEFAULT ''");
echo "Done.\n\n";

// Fix: allow contact_no to be nullable
$pdo->exec("ALTER TABLE clients MODIFY COLUMN contact_no VARCHAR(255) NOT NULL DEFAULT ''");
echo "Fixed contact_no column.\n\n";

// Now re-import missing clients
$csvFile = __DIR__ . '/pakistan-clients.csv';
$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle);
$header = array_map(function($h) { return trim($h, " \t\n\r\0\x0B\"'"); }, $header);

$now = date('Y-m-d H:i:s');
$imported = 0;
$skipped = 0;
$errors = 0;

$serviceMap = [];
$svc = $pdo->query("SELECT id, name, display_name FROM services");
while ($row = $svc->fetch(PDO::FETCH_ASSOC)) {
    $serviceMap[$row['display_name']] = $row['id'];
    $serviceMap[$row['name']] = $row['id'];
}

$validStatuses = ['Individual', 'AOP', 'Company'];

$stmt = $pdo->prepare("INSERT INTO clients (name, email, contact_no, status, folder_link, fbr_username, fbr_password, it_pin_code, secp_password, secp_pin, kpra_username, kpra_password, kpra_pin, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$svcStmt = $pdo->prepare("INSERT IGNORE INTO client_services (client_id, service_id, created_at, updated_at) VALUES (?, ?, ?, ?)");

while (($row = fgetcsv($handle)) !== false) {
    if (empty($row[0]) || trim($row[0]) === '') { $skipped++; continue; }

    $data = [];
    foreach ($header as $i => $col) { $data[$col] = isset($row[$i]) ? trim($row[$i]) : ''; }

    $name = $data['Name'] ?? '';
    if (empty($name)) { $skipped++; continue; }

    // Check if already exists
    $check = $pdo->prepare("SELECT id FROM clients WHERE name = ?");
    $check->execute([$name]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $clientId = $existing['id'];
        // Still link services for existing
        $activeServices = $data['Active Services'] ?? '';
        if (!empty($activeServices) && $activeServices !== '[]') {
            $activeServices = str_replace(['[', ']', '"', '""'], '', $activeServices);
            $serviceNames = array_map('trim', explode(',', $activeServices));
            foreach ($serviceNames as $svcName) {
                if (empty($svcName) || $svcName === 'Not Active') continue;
                if (isset($serviceMap[$svcName])) {
                    try { $svcStmt->execute([$clientId, $serviceMap[$svcName], $now, $now]); } catch (Exception $e) {}
                }
            }
        }
        $skipped++;
        continue;
    }

    $status = $data['Status'] ?? 'Individual';
    if (!in_array($status, $validStatuses)) $status = 'Individual';

    $email = $data['Email'] ?? '';
    $email = preg_replace('/\s*\/.*$/', '', $email);
    $email = preg_replace('/\s*pass.*$/i', '', $email);
    $email = trim($email);
    if ($email === '.' || $email === 'x' || $email === 'Added in Fair Tax International') $email = '';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';

    try {
        $stmt->execute([
            $name,
            $email,
            $data['Contact No.'] ?? '',
            $status,
            !empty($data['Folder Link']) ? $data['Folder Link'] : null,
            !empty($data['FBR Username']) ? $data['FBR Username'] : null,
            !empty($data['FBR Password']) ? $data['FBR Password'] : null,
            !empty($data['IT Pin Code']) ? $data['IT Pin Code'] : null,
            !empty($data['SECP Password']) ? $data['SECP Password'] : null,
            !empty($data['SECP Pin']) ? $data['SECP Pin'] : null,
            !empty($data['KPRA Username']) ? $data['KPRA Username'] : null,
            !empty($data['KPRA Password']) ? $data['KPRA Password'] : null,
            !empty($data['KPRA Pin']) ? $data['KPRA Pin'] : null,
            (!empty($data['Shareholders']) && $data['Shareholders'] !== '[]') ? "Shareholders: " . $data['Shareholders'] : null,
            $now, $now
        ]);
        $clientId = $pdo->lastInsertId();
        $imported++;
        echo "  OK: $name\n";

        // Link services
        $activeServices = $data['Active Services'] ?? '';
        if (!empty($activeServices) && $activeServices !== '[]') {
            $activeServices = str_replace(['[', ']', '"', '""'], '', $activeServices);
            $serviceNames = array_map('trim', explode(',', $activeServices));
            foreach ($serviceNames as $svcName) {
                if (empty($svcName) || $svcName === 'Not Active') continue;
                if (isset($serviceMap[$svcName])) {
                    try { $svcStmt->execute([$clientId, $serviceMap[$svcName], $now, $now]); } catch (Exception $e) {}
                }
            }
        }
    } catch (Exception $e) {
        echo "  ERROR: $name - " . $e->getMessage() . "\n";
        $errors++;
    }
}
fclose($handle);

$total = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

echo "\n========================================\n";
echo "IMPORT COMPLETE!\n";
echo "========================================\n";
echo "New imports: $imported\n";
echo "Skipped (already existed): $skipped\n";
echo "Errors: $errors\n";
echo "Total clients in database: $total\n";
echo "\nDelete this file for security!\n</pre>";
