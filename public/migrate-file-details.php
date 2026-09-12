<?php
/**
 * Room for the file register imported from the "Files details" sheet.
 *
 * That register names a party per file as free text, and most of those are
 * one-off matters rather than clients on the books. So client_id becomes
 * optional and client_name carries the register's own wording; a row shows the
 * linked client when there is one and the raw name otherwise.
 */
echo "<pre>";
$pdo = new PDO("mysql:host=localhost;dbname=fairtax1_fti_pak;charset=utf8mb4", "fairtax1_fti_pak", "Yousafzai1");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$has = function (string $table, string $column) use ($pdo): bool {
    return (bool) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = '{$table}' AND column_name = '{$column}'"
    )->fetchColumn();
};

if (!$has('file_numbers', 'client_name')) {
    $pdo->exec("ALTER TABLE `file_numbers` ADD COLUMN `client_name` VARCHAR(255) NULL AFTER `client_id`");
    echo "Added file_numbers.client_name\n";
} else {
    echo "file_numbers.client_name already present\n";
}

if (!$has('file_numbers', 'source')) {
    $pdo->exec("ALTER TABLE `file_numbers` ADD COLUMN `source` VARCHAR(30) NULL AFTER `description`");
    echo "Added file_numbers.source\n";
} else {
    echo "file_numbers.source already present\n";
}

// The register has files for parties who are not clients, so the foreign key
// has to allow NULL. Drop it by its real name rather than a guessed one.
$fk = $pdo->query(
    "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'file_numbers'
       AND COLUMN_NAME = 'client_id' AND REFERENCED_TABLE_NAME = 'clients'"
)->fetchColumn();

if ($fk) {
    $pdo->exec("ALTER TABLE `file_numbers` DROP FOREIGN KEY `{$fk}`");
    echo "Dropped foreign key {$fk}\n";
}

$pdo->exec("ALTER TABLE `file_numbers` MODIFY `client_id` BIGINT UNSIGNED NULL");
echo "file_numbers.client_id is now nullable\n";

$pdo->exec("ALTER TABLE `file_numbers`
            ADD CONSTRAINT `file_numbers_client_id_foreign`
            FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL");
echo "Re-added foreign key with ON DELETE SET NULL\n";

echo "\nDone!\n</pre>";
