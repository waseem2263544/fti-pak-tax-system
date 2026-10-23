<?php
/**
 * Firm registrations: the data behind Form A, the affidavit and the
 * partnership deed for a firm being registered with the Registrar of Firms.
 *
 * Boots the app and uses its own database connection, so no credentials
 * live in this file. Safe to run more than once.
 */
set_time_limit(120);
echo "<pre><h2>Firm registrations</h2>\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

if (Schema::hasTable('firm_registrations')) {
    exit("Table firm_registrations already exists - nothing to do.\n</pre>");
}

$migration = require __DIR__ . '/../database/migrations/2026_10_23_000001_create_firm_registrations_table.php';
$migration->up();
echo "Created: firm_registrations\n";

echo "\nDone!\n</pre>";
