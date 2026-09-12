<?php
/**
 * Let one line carry the balancing figure.
 *
 * Cash in hand is what a preparer plugs so the reconciliation comes to nil:
 * everything else is evidenced, and the notes and coins are whatever is left
 * over. Marking the line means the app derives it instead of asking for it.
 */
echo "<pre><h2>Balancing figure</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$has = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'wealth_lines' AND column_name = 'balancing'")->fetchColumn();

if (!$has) {
    $pdo->exec("ALTER TABLE `wealth_lines` ADD COLUMN `balancing` TINYINT(1) NOT NULL DEFAULT 0 AFTER `code`");
    echo "Added wealth_lines.balancing\n";
} else {
    echo "wealth_lines.balancing already present\n";
}

// Any client with exactly one cash line and none marked gets it marked, since
// that is the line a preparer would have been plugging by hand.
$rows = $pdo->query("
    SELECT client_id, MIN(id) AS line_id, COUNT(*) AS n
    FROM wealth_lines
    WHERE code = '7012'
      AND client_id NOT IN (SELECT client_id FROM wealth_lines WHERE balancing = 1)
    GROUP BY client_id HAVING n = 1
")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("UPDATE wealth_lines SET balancing = 1 WHERE id = ?");
foreach ($rows as $r) { $stmt->execute([$r['line_id']]); }
echo "Marked " . count($rows) . " existing cash lines as the balancing figure.\n";

echo "\nDone!\n</pre>";
