<?php
/**
 * wht-challan-files.php — locate the old portal's challan uploads and move them
 * into private storage.
 *
 * The importer left these behind because the old docroot path was unknown.
 * This searches the account for it, then copies each referenced file into
 * storage/app/wht-challans/<company>/ and points wht_challans at it.
 *
 * Add ?dry=1 to preview. Delete this file after use.
 */
set_time_limit(300);
echo "<pre><h2>Challan Documents</h2>\n";
$dry = isset($_GET['dry']);
if ($dry) echo "DRY RUN — nothing will be written.\n\n";

$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$src = new PDO("mysql:host=localhost;dbname=fairtax1_wht;charset=utf8mb4", 'fairtax1_wht', '47yTehPqSv63hSUVAnLn', $opts);
$dst = new PDO("mysql:host=localhost;dbname={$env['DB_DATABASE']};charset=utf8mb4", $env['DB_USERNAME'], $env['DB_PASSWORD'], $opts);

// Which files are we looking for?
$wanted = [];
foreach ($src->query("SELECT * FROM challan_files") as $r) {
    foreach (['psid_file', 'cpr_file'] as $f) {
        if (!empty($r[$f])) $wanted[basename($r[$f])] = true;
    }
}
echo "Files referenced by the old portal: " . count($wanted) . "\n\n";

if (!$wanted) { exit("Nothing to copy.\n</pre>"); }

// ── Find the directory ──
echo "── Searching ──\n";
// This account nests sites as /home/<user>/domains/<domain>/public_html, so
// start above the domains directory and allow enough depth to reach
// <domain>/public_html/uploads/challans.
$roots = array_unique(array_filter([
    dirname(__DIR__, 3),   // /home/<user>/domains
    dirname(__DIR__, 4),   // /home/<user>
    dirname(__DIR__, 2),
], 'is_dir'));

$sample = array_key_first($wanted);
$found = null;

foreach ($roots as $root) {
    echo "  scanning $root\n";

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD   // skip unreadable dirs
        );
        $it->setMaxDepth(6);

        foreach ($it as $path => $info) {
            if (!$info->isDir() || basename($path) !== 'challans') continue;

            if (is_readable("$path/$sample")) {
                $found = $path;
                break 2;
            }
            echo "    (challans dir without the referenced files: $path)\n";
        }
    } catch (Throwable $e) {
        echo "    scan stopped: " . $e->getMessage() . "\n";
    }
}

if (!$found) {
    echo "\n  Could not locate the uploads/challans directory.\n";
    echo "  The database records are already imported; only the PDFs are missing.\n";
    exit("</pre>");
}
echo "  found: $found\n\n";

// ── Copy ──
echo "── Copying ──\n";
$storageBase = dirname(__DIR__) . '/storage/app/wht-challans';
$copied = 0; $missing = 0; $linked = 0;

foreach ($src->query("SELECT * FROM challan_files") as $r) {
    // Map the old company id to the new one.
    $q = $dst->prepare("SELECT id FROM wht_companies WHERE legacy_id = ?");
    $q->execute([$r['company_id']]);
    $companyId = $q->fetchColumn();
    if (!$companyId) continue;

    $find = $dst->prepare("SELECT * FROM wht_challans WHERE wht_company_id = ? AND psid_no = ?");
    $find->execute([$companyId, $r['psid_no']]);
    $challan = $find->fetch();
    if (!$challan) continue;

    $update = [];

    foreach (['psid_file', 'cpr_file'] as $field) {
        if (empty($r[$field])) continue;

        $name = basename($r[$field]);
        $source = "$found/$name";

        if (!is_readable($source)) { echo "  ! missing: $name\n"; $missing++; continue; }

        $destDir = "$storageBase/$companyId";
        if (!$dry && !is_dir($destDir)) @mkdir($destDir, 0775, true);

        if ($dry) { echo "  + $name\n"; $copied++; continue; }

        if (copy($source, "$destDir/$name")) {
            $update[$field] = "wht-challans/$companyId/$name";
            echo "  + $name\n";
            $copied++;
        }
    }

    if (!$dry && $update) {
        $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($update)));
        $u = $dst->prepare("UPDATE wht_challans SET $sets, updated_at = NOW() WHERE id = ?");
        $u->execute([...array_values($update), $challan['id']]);
        $linked++;
    }
}

echo "\n" . str_repeat('=', 56) . "\n";
echo $dry ? "DRY RUN COMPLETE\n" : "COMPLETE\n";
echo "Copied: $copied · missing: $missing · challan records updated: $linked\n";
echo "</pre>";
