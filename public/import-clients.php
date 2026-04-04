<?php
set_time_limit(300);
echo "<pre><h2>Importing Clients from CSV</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Read CSV from same directory
$csvFile = __DIR__ . '/pakistan-clients.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found at: $csvFile\nUpload pakistan-clients.csv to public_html/public/\n");
}

$handle = fopen($csvFile, 'r');
// Skip BOM and header
$header = fgetcsv($handle);
// Clean BOM and quotes from headers
$header[0] = preg_replace('/[\x{FEFF}]/u', '', $header[0]);
$header = array_map(function($h) { return trim($h, " \t\n\r\0\x0B\"'"); }, $header);

echo "CSV Headers: " . implode(' | ', $header) . "\n\n";

// Map CSV columns to DB columns
$colMap = [
    'Name' => 'name',
    'Email' => 'email',
    'Contact No.' => 'contact_no',
    'Status' => 'status',
    'Folder Link' => 'folder_link',
    'FBR Username' => 'fbr_username',
    'FBR Password' => 'fbr_password',
    'IT Pin Code' => 'it_pin_code',
    'SECP Password' => 'secp_password',
    'SECP Pin' => 'secp_pin',
    'KPRA Username' => 'kpra_username',
    'KPRA Password' => 'kpra_password',
    'KPRA Pin' => 'kpra_pin',
];

$validStatuses = ['Individual', 'AOP', 'Company'];
$now = date('Y-m-d H:i:s');
$imported = 0;
$skipped = 0;
$errors = 0;

// Get existing service IDs
$serviceMap = [];
$svc = $pdo->query("SELECT id, name, display_name FROM services");
while ($row = $svc->fetch(PDO::FETCH_ASSOC)) {
    $serviceMap[$row['display_name']] = $row['id'];
    $serviceMap[$row['name']] = $row['id'];
}

// Prepare client insert
$stmt = $pdo->prepare("INSERT INTO clients (name, email, contact_no, status, folder_link, fbr_username, fbr_password, it_pin_code, secp_password, secp_pin, kpra_username, kpra_password, kpra_pin, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Prepare client_services insert
$svcStmt = $pdo->prepare("INSERT IGNORE INTO client_services (client_id, service_id, created_at, updated_at) VALUES (?, ?, ?, ?)");

$clientNameToId = [];

while (($row = fgetcsv($handle)) !== false) {
    // Skip empty rows
    if (empty($row[0]) || trim($row[0]) === '') {
        $skipped++;
        continue;
    }

    $data = [];
    foreach ($header as $i => $col) {
        $data[$col] = isset($row[$i]) ? trim($row[$i]) : '';
    }

    $name = $data['Name'] ?? '';
    if (empty($name)) { $skipped++; continue; }

    // Fix status
    $status = $data['Status'] ?? 'Individual';
    if (!in_array($status, $validStatuses)) {
        $status = 'Individual'; // default for "Check" or other invalid values
    }

    // Clean email (take first email if multiple)
    $email = $data['Email'] ?? '';
    $email = preg_replace('/\s*\/.*$/', '', $email); // remove everything after /
    $email = preg_replace('/\s*pass.*$/i', '', $email); // remove "pass:..."
    $email = trim($email);
    if ($email === '.' || $email === 'x' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = '';
    }
    if (empty($email)) $email = null;

    $contactNo = !empty($data['Contact No.']) ? $data['Contact No.'] : null;
    $folderLink = !empty($data['Folder Link']) ? $data['Folder Link'] : null;
    $fbrUsername = !empty($data['FBR Username']) ? $data['FBR Username'] : null;
    $fbrPassword = !empty($data['FBR Password']) ? $data['FBR Password'] : null;
    $itPinCode = !empty($data['IT Pin Code']) ? $data['IT Pin Code'] : null;
    $secpPassword = !empty($data['SECP Password']) ? $data['SECP Password'] : null;
    $secpPin = !empty($data['SECP Pin']) ? $data['SECP Pin'] : null;
    $kpraUsername = !empty($data['KPRA Username']) ? $data['KPRA Username'] : null;
    $kpraPassword = !empty($data['KPRA Password']) ? $data['KPRA Password'] : null;
    $kpraPin = !empty($data['KPRA Pin']) ? $data['KPRA Pin'] : null;

    // Store shareholders info as notes
    $shareholders = $data['Shareholders'] ?? '';
    $notes = '';
    if (!empty($shareholders) && $shareholders !== '[]') {
        $notes = "Shareholders: " . $shareholders;
    }

    try {
        // Check if client already exists
        $check = $pdo->prepare("SELECT id FROM clients WHERE name = ?");
        $check->execute([$name]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $clientId = $existing['id'];
            echo "  SKIP (exists): $name\n";
        } else {
            $stmt->execute([
                $name, $email, $contactNo ?? '', $status, $folderLink,
                $fbrUsername, $fbrPassword, $itPinCode,
                $secpPassword, $secpPin, $kpraUsername, $kpraPassword, $kpraPin,
                $notes ?: null, $now, $now
            ]);
            $clientId = $pdo->lastInsertId();
            $imported++;
            echo "  OK: $name\n";
        }

        $clientNameToId[$name] = $clientId;

        // Link services
        $activeServices = $data['Active Services'] ?? '';
        if (!empty($activeServices) && $activeServices !== '[]') {
            // Parse JSON-like array: ["Income Tax Return","Sales Tax Return"]
            $activeServices = str_replace(['[', ']', '"', '""'], '', $activeServices);
            $serviceNames = array_map('trim', explode(',', $activeServices));
            foreach ($serviceNames as $svcName) {
                if (empty($svcName) || $svcName === 'Not Active') continue;
                if (isset($serviceMap[$svcName])) {
                    try {
                        $svcStmt->execute([$clientId, $serviceMap[$svcName], $now, $now]);
                    } catch (Exception $e) {
                        // duplicate, ignore
                    }
                }
            }
        }

    } catch (Exception $e) {
        echo "  ERROR: $name - " . $e->getMessage() . "\n";
        $errors++;
    }
}

fclose($handle);

echo "\n========================================\n";
echo "IMPORT COMPLETE!\n";
echo "========================================\n";
echo "Imported: $imported\n";
echo "Skipped:  $skipped\n";
echo "Errors:   $errors\n";
echo "\nDelete this file and pakistan-clients.csv for security!\n";
echo "</pre>";
