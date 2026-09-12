<?php
/**
 * Room for the letter register imported from the "Ref No." sheet.
 *
 * That register is fifteen years of hand-kept rows, so the table has to accept
 * what is actually in it: parties who were never clients on the books, 212 rows
 * with no usable date, 47 with no description, and eight dates typed wrongly
 * enough that no reading is safe. Those keep their original text in raw_date
 * rather than being guessed at.
 */
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$has = function (string $t, string $c) use ($pdo): bool {
    return (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = '{$t}' AND column_name = '{$c}'")->fetchColumn();
};

foreach ([
    'client_name' => "VARCHAR(255) NULL AFTER `client_id`",
    'raw_date'    => "VARCHAR(60) NULL AFTER `date`",
    'source'      => "VARCHAR(30) NULL",
] as $col => $ddl) {
    if (!$has('letter_numbers', $col)) {
        $pdo->exec("ALTER TABLE `letter_numbers` ADD COLUMN `{$col}` {$ddl}");
        echo "Added letter_numbers.{$col}\n";
    } else {
        echo "letter_numbers.{$col} already present\n";
    }
}

$fk = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'letter_numbers'
      AND COLUMN_NAME = 'client_id' AND REFERENCED_TABLE_NAME = 'clients'")->fetchColumn();

if ($fk) {
    $pdo->exec("ALTER TABLE `letter_numbers` DROP FOREIGN KEY `{$fk}`");
    echo "Dropped foreign key {$fk}\n";
}

foreach ([
    "MODIFY `client_id` BIGINT UNSIGNED NULL"        => 'client_id nullable',
    "MODIFY `date` DATE NULL"                        => 'date nullable',
    "MODIFY `description` VARCHAR(500) NULL"         => 'description nullable',
    "MODIFY `sequence_no` INT UNSIGNED NULL"         => 'sequence_no nullable',
    "MODIFY `year` INT UNSIGNED NULL"                => 'year nullable',
] as $ddl => $label) {
    $pdo->exec("ALTER TABLE `letter_numbers` {$ddl}");
    echo "letter_numbers.{$label}\n";
}

$pdo->exec("ALTER TABLE `letter_numbers`
            ADD CONSTRAINT `letter_numbers_client_id_foreign`
            FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL");
echo "Re-added foreign key with ON DELETE SET NULL\n";

echo "\nDone!\n</pre>";
