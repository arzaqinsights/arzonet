<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Super Admin
        \App\Models\User::updateOrCreate(
            ['email' => 'arzonetmail@gmail.com'],
            [
                'name' => 'Arzonet Super Admin',
                'password' => \Hash::make('Arzonet@78692'),
                'is_super_admin' => true,
                'role' => 'admin'
            ]
        );

        // 2. Initial Pricing Config
        \App\Models\GlobalSetting::set('pricing_rules', [
            'contacts_base_price' => 200, // Price per 1000 contacts
            'emails_base_price'   => 100, // Price per 1000 emails
            'discounts' => [
                ['min' => 10000,  'percent' => 10],
                ['min' => 50000,  'percent' => 20],
                ['min' => 100000, 'percent' => 30],
            ],
            'currency' => 'INR',
            'tax_percent' => 0, // GST (0 = disabled; change in Super Admin Settings or config/plans.php)
        ]);
        
        echo "Super Admin seeded successfully!\n";
    }
}
