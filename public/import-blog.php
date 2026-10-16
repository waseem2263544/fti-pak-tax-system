<?php
/**
 * Load the existing posts out of the website's constants file.
 *
 * Re-runnable: a post is keyed on region and slug, so running this again
 * refreshes what is there rather than duplicating it. Everything imported is
 * marked published, because it already is - it is live on the site.
 */
set_time_limit(300);
echo "<pre><h2>Blog import</h2>\n";

if (($_GET['secret'] ?? '') !== 'fti2026deploy') { http_response_code(403); exit("Forbidden\n"); }

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$path = dirname(__DIR__) . '/database/data/blog-posts.json';
if (!is_readable($path)) { exit("Missing {$path}\n"); }

$posts = json_decode(file_get_contents($path), true);
if (!is_array($posts)) { exit("Could not read the post file.\n"); }

printf("In the file: %d posts\n\n", count($posts));

$find = $pdo->prepare("SELECT id FROM posts WHERE region = ? AND slug = ?");
$ins = $pdo->prepare("INSERT INTO posts
    (region, slug, title, excerpt, content, category, read_time, hero_image, hero_image_alt,
     featured, status, published_on, reviewed_on, sort_order, created_at, updated_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,'published',?,?,?,NOW(),NOW())");
$upd = $pdo->prepare("UPDATE posts SET title = ?, excerpt = ?, content = ?, category = ?, read_time = ?,
     hero_image = ?, hero_image_alt = ?, featured = ?, published_on = ?, reviewed_on = ?,
     sort_order = ?, updated_at = NOW() WHERE id = ?");

$added = $updated = 0;

foreach ($posts as $p) {
    $row = [
        $p['title'] ?? '',
        $p['excerpt'] ?? null,
        $p['content'] ?? null,
        $p['category'] ?? null,
        $p['readTime'] ?? null,
        $p['heroImage'] ?? null,
        $p['heroImageAlt'] ?? null,
        !empty($p['featured']) ? 1 : 0,
        $p['date'] ?? null,
        $p['updated'] ?? null,
        (int) ($p['sort_order'] ?? 0),
    ];

    $find->execute([$p['region'], $p['slug']]);

    if ($id = $find->fetchColumn()) {
        $upd->execute(array_merge($row, [$id]));
        $updated++;
    } else {
        $ins->execute(array_merge([$p['region'], $p['slug']], $row));
        $added++;
    }
}

printf("Inserted %d, refreshed %d.\n", $added, $updated);

foreach ($pdo->query("SELECT region, status, COUNT(*) n FROM posts GROUP BY region, status") as $r) {
    printf("  %-4s %-10s %d\n", $r['region'], $r['status'], $r['n']);
}

echo "\nDone!\n</pre>";
