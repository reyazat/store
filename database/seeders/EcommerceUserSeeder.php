<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EcommerceUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding e-commerce users...');

        // Create admin user
        $admin = User::firstOrCreate(['email' => 'admin@shop.com'], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $this->command->line('   ✓ Created admin: admin@shop.com (password: password)');

        // Create demo customers
        $customers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+1234567891',
            ],
            [
                'name' => 'Ali Rezaei',
                'email' => 'ali@example.com',
                'phone' => '+989123456789',
            ],
        ];

        foreach ($customers as $customerData) {
            // Check if user exists by email OR phone
            $existingUser = User::where('email', $customerData['email'])
                ->orWhere('phone', $customerData['phone'])
                ->first();

            if ($existingUser) {
                $this->command->line("   ⚠ Skipped: {$customerData['email']} (already exists)");
                continue;
            }

            $customer = User::create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'] ?? null,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $this->command->line("   ✓ Created customer: {$customer->email}");
        }

        $this->command->info('✅ E-commerce users seeded successfully!');
    }
}
