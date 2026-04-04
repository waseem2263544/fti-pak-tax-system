<?php
// Auto-deploy webhook - triggered by GitHub on git push
// URL: https://app.fairtaxint.com/deploy.php?secret=fti2026deploy

$secret = 'fti2026deploy';

if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    http_response_code(403);
    die('Forbidden');
}

$basePath = dirname(__DIR__);

echo "<pre>";
echo "Deploying...\n\n";

// Download latest zip from GitHub
$zipUrl = 'https://github.com/waseem2263544/fti-pak-tax-system/archive/refs/heads/main.zip';
$zipFile = $basePath . '/storage/deploy-temp.zip';

echo "Downloading latest code from GitHub...\n";
$ch = curl_init($zipUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$data) {
    die("Failed to download from GitHub (HTTP $httpCode)\n");
}

file_put_contents($zipFile, $data);
echo "Downloaded (" . round(strlen($data) / 1024 / 1024, 1) . " MB)\n";

// Extract
$zip = new ZipArchive;
if ($zip->open($zipFile) === true) {
    $extractPath = $basePath . '/storage/deploy-extract';

    // Clean extract directory
    if (is_dir($extractPath)) {
        shell_exec("rm -rf " . escapeshellarg($extractPath));
    }

    $zip->extractTo($extractPath);
    $zip->close();
    echo "Extracted.\n";

    // Find the extracted folder name (GitHub adds repo-branch prefix)
    $dirs = glob($extractPath . '/fti-pak-tax-system-*');
    if (empty($dirs)) {
        die("Could not find extracted directory\n");
    }
    $sourceDir = $dirs[0];

    // Sync files (skip .env, storage/logs, vendor for speed)
    $protectedFiles = ['.env', 'storage/logs', 'storage/framework/sessions'];

    // Copy new files over
    echo "Syncing files...\n";
    shell_exec("rsync -a --exclude='.env' --exclude='storage/logs/*' --exclude='storage/framework/sessions/*' " . escapeshellarg($sourceDir . '/') . " " . escapeshellarg($basePath . '/'));

    echo "Files synced.\n";

    // Cleanup
    unlink($zipFile);
    shell_exec("rm -rf " . escapeshellarg($extractPath));
    echo "Cleanup done.\n";

    echo "\n✅ Deploy complete!\n";
} else {
    die("Failed to extract zip\n");
}

echo "</pre>";
