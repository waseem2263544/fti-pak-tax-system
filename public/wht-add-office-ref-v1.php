<?php
/**
 * wht-add-office-ref-v1.php — one-shot: add wht_companies.office_reference.
 * IRIS requires the withholding agent's office reference on the statement and
 * the app had nowhere to keep it. Overwrites itself when done.
 */
if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(404); exit('Not Found'); }

header('Content-Type: text/plain; charset=utf-8');

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}

$db = new PDO("mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$has = $db->prepare("SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = ? AND table_name = 'wht_companies' AND column_name = 'office_reference'");
$has->execute([$env['DB_DATABASE']]);

if ($has->fetchColumn()) {
    echo "Column already present.\n";
} else {
    $db->exec("ALTER TABLE wht_companies ADD COLUMN `office_reference` VARCHAR(50) NULL AFTER `ntn_cnic`");
    echo "Added wht_companies.office_reference\n";
}

echo "\nAgents:\n";
foreach ($db->query("SELECT name, ntn_cnic, office_reference FROM wht_companies ORDER BY name") as $r) {
    printf("  %-30s NTN %-12s office ref %s\n", $r['name'], $r['ntn_cnic'] ?: '—',
        $r['office_reference'] ?: 'NOT SET — add it under Setup → Withholding Agents');
}

@file_put_contents(__FILE__, "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain');\necho \"Gone. This one-shot script has already run.\\n\";\n");
echo "\nScript neutralised.\n";
