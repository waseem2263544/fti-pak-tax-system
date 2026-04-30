<?php
set_time_limit(300);
$secret = 'fti2026deploy';
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) { http_response_code(403); die('Forbidden'); }

echo "<pre>Deploying...\n\n";
ob_flush(); flush();

$basePath = dirname(__DIR__);
$zipFile = $basePath . '/storage/deploy-temp.zip';

// Download
$zipUrl = 'https://github.com/waseem2263544/fti-pak-tax-system/archive/refs/heads/main.zip';
$ch = curl_init($zipUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$data || $httpCode !== 200) { die("Download failed (HTTP $httpCode)\n"); }
file_put_contents($zipFile, $data);
echo "Downloaded.\n";
ob_flush(); flush();

// Extract only app code, skip vendor/
$zip = new ZipArchive;
if ($zip->open($zipFile) !== true) { die("Cannot open zip\n"); }

$prefix = $zip->getNameIndex(0);
$skip = ['.env', 'storage/logs/', 'storage/framework/sessions/', 'storage/framework/views/', 'node_modules/'];

$count = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    $relativePath = substr($name, strlen($prefix));
    if ($relativePath === '' || $relativePath === false) continue;

    $skipThis = false;
    foreach ($skip as $s) {
        if ($relativePath === $s || strpos($relativePath, $s) === 0) { $skipThis = true; break; }
    }
    if ($skipThis) continue;

    $targetPath = $basePath . '/' . $relativePath;

    if (substr($name, -1) === '/') {
        if (!is_dir($targetPath)) mkdir($targetPath, 0755, true);
        continue;
    }

    $dir = dirname($targetPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $content = $zip->getFromIndex($i);
    if ($content !== false) {
        file_put_contents($targetPath, $content);
        $count++;
    }
}

$zip->close();
unlink($zipFile);

// Clear view cache
$viewCachePath = $basePath . '/storage/framework/views';
if (is_dir($viewCachePath)) {
    $cached = glob($viewCachePath . '/*.php');
    foreach ($cached as $file) { unlink($file); }
    echo "Cleared " . count($cached) . " cached views.\n";
}

echo "Synced $count files.\n\nDEPLOY COMPLETE!\n</pre>";
