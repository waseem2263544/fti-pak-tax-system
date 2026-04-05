<?php
$content = file_get_contents('public/pakistan-clients.csv');
$content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // Remove BOM
$lines = explode("\n", $content);

$header = str_getcsv(array_shift($lines));
$header = array_map(function($h) { return trim($h, " \t\n\r\0\x0B\"'"); }, $header);

echo "Header: " . implode(' | ', $header) . "\n";

$clients = [];
$buffer = '';
foreach ($lines as $line) {
    $buffer .= $line;
    // Check if we have balanced quotes (complete CSV row)
    if ((substr_count($buffer, '"') % 2) === 0) {
        $row = str_getcsv($buffer);
        $data = [];
        foreach ($header as $i => $col) {
            $data[$col] = isset($row[$i]) ? trim($row[$i]) : '';
        }
        if (!empty($data['Name'])) {
            $clients[] = $data;
        }
        $buffer = '';
    } else {
        $buffer .= "\n"; // multiline field, keep reading
    }
}

file_put_contents('public/clients-data.php', "<?php\nreturn " . var_export($clients, true) . ";\n");
echo "Written " . count($clients) . " clients to public/clients-data.php\n";
