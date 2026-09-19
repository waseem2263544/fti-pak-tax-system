<?php
/**
 * Which challan tab a section belongs to.
 *
 * FBR's payment page splits by regime before anything else: Adjustable Income
 * Tax and Fixed/Final Income Tax are different tabs with different payment
 * codes. A batch cannot straddle them.
 *
 * Everything defaults to adjustable, which is right for the payments a
 * withholding agent usually makes - s.149 salary, s.153 goods and services,
 * s.155 rent. The exceptions are a matter of the Ordinance as it stands, so
 * they are set in Setup rather than guessed at here.
 */
echo "<pre><h2>Section regime</h2>\n";

$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$has = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'wht_sections' AND column_name = 'regime'")->fetchColumn();

if (!$has) {
    $pdo->exec("ALTER TABLE `wht_sections`
                ADD COLUMN `regime` ENUM('adjustable','final') NOT NULL DEFAULT 'adjustable' AFTER `applies_to`");
    echo "Added wht_sections.regime, defaulting to adjustable\n";
} else {
    echo "wht_sections.regime already present\n";
}

printf("\n%d sections adjustable, %d final.\n",
    $pdo->query("SELECT COUNT(*) FROM wht_sections WHERE regime = 'adjustable'")->fetchColumn(),
    $pdo->query("SELECT COUNT(*) FROM wht_sections WHERE regime = 'final'")->fetchColumn());

echo "\nSet the final-tax ones under Setup > Sections.\n";
echo "\nDone!\n</pre>";
