<?php

namespace IamApi\Database\Seeders;

use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    /**
     * Creates test users for development environment.
     * 
     * Uses OrganizationService::addMember() to ensure Layer 1 + Layer 2 sync:
     * - Sets organization_id on user (Layer 1)
     * - Assigns Spatie role in organization context (Layer 2)
     * 
     * User Types Created:
     * - Store members (admin, editor, member) for existing organizations
     * - Buyers (organization_id = null) for testing buyer flows
     * - Edge cases: locked, expired password, unverified, inactive
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding test users...');

        // Get organizations created by OrganizationSeeder
        $digiStore = Organization::where('name', 'دیجی‌استور')->first();
        $technolife = Organization::where('name', 'تکنولایف مارکت')->first();
        $fashionland = Organization::where('name', 'فشن‌لند')->first();

        if (!$digiStore || !$technolife || !$fashionland) {
            $this->command->error('❌ Organizations not found. Run OrganizationSeeder first!');
            return;
        }

        // Store members — will be assigned via addMember (Layer 1 + 2)
        $storeMembers = [
            [
                'name' => 'رضا کریمی',
                'email' => 'reza@digistore.com',
                'phone' => '+989151234001',
                'organization' => $digiStore,
                'role' => 'admin',
            ],
            [
                'name' => 'زهرا حسینی',
                'email' => 'zahra@digistore.com',
                'phone' => '+989151234002',
                'organization' => $digiStore,
                'role' => 'editor',
            ],
            [
                'name' => 'حسین نوری',
                'email' => 'hossein@technolife.com',
                'phone' => '+989151234003',
                'organization' => $technolife,
                'role' => 'admin',
            ],
            [
                'name' => 'فاطمه عباسی',
                'email' => 'fatemeh@technolife.com',
                'phone' => '+989151234004',
                'organization' => $technolife,
                'role' => 'editor',
            ],
            [
                'name' => 'محمد جعفری',
                'email' => 'mohammad@fashionland.com',
                'phone' => '+989151234005',
                'organization' => $fashionland,
                'role' => 'member',
            ],
        ];

        foreach ($storeMembers as $memberData) {
            $user = User::firstOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'phone' => $memberData['phone'],
                    'password' => Hash::make('User123!'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'status' => 'active',
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'password_expires_at' => now()->addDays(90),
                ]
            );

            // Add to organization (Layer 1 + Layer 2 sync via service)
            $this->organizationService->addMember(
                $memberData['organization'],
                $user,
                $memberData['role']
            );

            $this->command->line("   ✓ Store {$memberData['role']}: {$user->email} → {$memberData['organization']->name}");
        }

        // Buyers — no organization, these are regular customers
        $buyers = [
            [
                'name' => 'نیما صادقی',
                'email' => 'nima@gmail.com',
                'phone' => '+989161234001',
            ],
            [
                'name' => 'لیلا کاظمی',
                'email' => 'leila@gmail.com',
                'phone' => '+989161234002',
            ],
            [
                'name' => 'امیر حسینی',
                'email' => 'amir@gmail.com',
                'phone' => '+989161234003',
            ],
        ];

        foreach ($buyers as $buyerData) {
            $user = User::firstOrCreate(
                ['email' => $buyerData['email']],
                [
                    'name' => $buyerData['name'],
                    'phone' => $buyerData['phone'],
                    'password' => Hash::make('User123!'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'status' => 'active',
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'password_expires_at' => now()->addDays(90),
                ]
            );

            $this->command->line("   ✓ Buyer: {$user->email} (organization_id = null)");
        }

        // Edge cases for testing
        $edgeCases = [
            [
                'name' => 'کاربر قفل‌شده',
                'email' => 'locked@test.com',
                'phone' => '+989171234001',
                'locked_until' => now()->addHours(2),
                'failed_login_attempts' => 10,
            ],
            [
                'name' => 'کاربر تایید نشده',
                'email' => 'unverified@test.com',
                'phone' => '+989171234002',
                'email_verified_at' => null,
            ],
            [
                'name' => 'کاربر غیرفعال',
                'email' => 'inactive@test.com',
                'phone' => '+989171234003',
                'status' => 'inactive',
            ],
            [
                'name' => 'رمز منقضی',
                'email' => 'expired-pass@test.com',
                'phone' => '+989171234004',
                'must_change_password' => true,
                'password_expires_at' => now()->subDays(10),
            ],
        ];

        foreach ($edgeCases as $caseData) {
            User::firstOrCreate(
                ['email' => $caseData['email']],
                [
                    'name' => $caseData['name'],
                    'phone' => $caseData['phone'],
                    'password' => Hash::make('User123!'),
                    'email_verified_at' => $caseData['email_verified_at'] ?? now(),
                    'phone_verified_at' => now(),
                    'status' => $caseData['status'] ?? 'active',
                    'must_change_password' => $caseData['must_change_password'] ?? false,
                    'password_changed_at' => now()->subDays(100),
                    'password_expires_at' => $caseData['password_expires_at'] ?? now()->addDays(90),
                    'failed_login_attempts' => $caseData['failed_login_attempts'] ?? 0,
                    'locked_until' => $caseData['locked_until'] ?? null,
                ]
            );

            $this->command->line("   ✓ Edge case: {$caseData['email']}");
        }

        $this->command->info('✅ Test users seeded successfully!');
        $this->command->line('   Default password: User123!');
    }
}
