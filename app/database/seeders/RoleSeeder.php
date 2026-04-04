<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full system access'
        ]);

        Role::create([
            'name' => 'consultant',
            'display_name' => 'Tax Consultant',
            'description' => 'Consultant with client and task management access'
        ]);

        Role::create([
            'name' => 'staff',
            'display_name' => 'Staff Member',
            'description' => 'Staff member with limited access'
        ]);
    }
}
