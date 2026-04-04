<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupApplication extends Command
{
    protected $signature = 'app:setup';
    protected $description = 'Setup the application (run migrations, seed data, create admin)';

    public function handle()
    {
        $this->info('Starting application setup...');

        // Run migrations
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);

        // Seed data
        $this->info('Seeding default data...');
        Artisan::call('db:seed', ['--force' => true]);

        // Create admin user
        $this->info('Creating admin user...');
        $this->createAdminUser();

        $this->info('Setup completed successfully!');
    }

    private function createAdminUser()
    {
        $name = $this->ask('Admin user name', 'Admin');
        $email = $this->ask('Admin email address');
        $password = $this->secret('Admin password');

        $user = \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->roles()->attach($adminRole);
        }

        $this->info("Admin user created: {$email}");
    }
}
