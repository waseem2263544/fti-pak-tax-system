<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::create([
            'name' => 'income_tax_return',
            'display_name' => 'Income Tax Return',
            'description' => 'Annual income tax return filing',
            'default_reminder_days' => 30,
            'default_deadline_days' => 365
        ]);

        Service::create([
            'name' => 'sales_tax_return',
            'display_name' => 'Sales Tax Return',
            'description' => 'Monthly/Quarterly sales tax return',
            'default_reminder_days' => 7,
            'default_deadline_days' => 30
        ]);

        Service::create([
            'name' => 'kpra_return',
            'display_name' => 'KPRA Return',
            'description' => 'KPRA (Federal Board of Revenue) return filing',
            'default_reminder_days' => 15,
            'default_deadline_days' => 90
        ]);

        Service::create([
            'name' => 'bookkeeping',
            'display_name' => 'Bookkeeping',
            'description' => 'Monthly bookkeeping and accounting services',
            'default_reminder_days' => 3,
            'default_deadline_days' => 30
        ]);

        Service::create([
            'name' => 'withholding_tax_statement',
            'display_name' => 'Withholding Tax Statements',
            'description' => 'Withholding tax certificate generation and filing',
            'default_reminder_days' => 7,
            'default_deadline_days' => 60
        ]);
    }
}
