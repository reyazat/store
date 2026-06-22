<?php

namespace Database\Seeders;

use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EcommerceUserSeeder extends Seeder
{
    /**
     * Creates buyer users for e-commerce demo data.
     * 
     * These are regular customers (organization_id = null) who can:
     * - Browse products, add to cart, checkout
     * - Access /v1/user/* routes
     * - Cannot access /v1/store/* or /v1/admin/*
     * 
     * Note: Store owners/members are created by OrganizationSeeder + TestUsersSeeder.
     * Super Admin is created by SuperAdminSeeder.
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding e-commerce buyer users...');

        $buyers = [
            [
                'name' => 'محسن کریمی',
                'email' => 'mohsen@buyer.com',
                'phone' => '+989181234001',
            ],
            [
                'name' => 'نازنین فراهانی',
                'email' => 'nazanin@buyer.com',
                'phone' => '+989181234002',
            ],
            [
                'name' => 'Ali Rezaei',
                'email' => 'ali@buyer.com',
                'phone' => '+989181234003',
            ],
        ];

        foreach ($buyers as $buyerData) {
            $user = User::firstOrCreate(
                ['email' => $buyerData['email']],
                [
                    'name' => $buyerData['name'],
                    'phone' => $buyerData['phone'],
                    'password' => Hash::make('Buyer123!'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'status' => 'active',
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'password_expires_at' => now()->addDays(90),
                    // organization_id = null (buyer — not part of any store)
                ]
            );

            $this->command->line("   ✓ Buyer: {$user->email} (organization_id = null)");
        }

        $this->command->info('✅ E-commerce buyers seeded! (password: Buyer123!)');
    }
}
