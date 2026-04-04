<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2>FTI Pak Setup</h2><pre>";

// Run migrations
echo "Running migrations...\n";
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();

// Run seeders
echo "\nRunning seeders...\n";
Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();

// Generate app key if not set
echo "\nGenerating app key...\n";
Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();

// Create admin user
try {
    $user = \App\Models\User::where('email', 'admin@fairtaxint.com')->first();
    if (!$user) {
        $user = \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@fairtaxint.com',
            'password' => bcrypt('admin123'),
        ]);

        $adminRole = \App\Models\Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $user->roles()->attach($adminRole->id);
        }
        echo "\nAdmin user created!\n";
        echo "Email: admin@fairtaxint.com\n";
        echo "Password: admin123\n";
    } else {
        echo "\nAdmin user already exists.\n";
    }
} catch (Exception $e) {
    echo "\nNote: " . $e->getMessage() . "\n";
}

echo "\n\n✅ Setup complete! Delete this file (setup.php) for security.\n";
echo "</pre>";
