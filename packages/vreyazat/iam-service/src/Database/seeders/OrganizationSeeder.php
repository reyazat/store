<?php

namespace IamApi\Database\Seeders;

use IamApi\Models\User;
use IamApi\Services\OrganizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    /**
     * Creates test organizations with owners for development.
     * 
     * Uses OrganizationService::createOrganization() which:
     * - Creates the organization
     * - Sets organization_id on owner (Layer 1)
     * - Dispatches OrganizationCreated event → creates default roles/permissions (Layer 2)
     * - Assigns 'owner' Spatie role to the owner user
     */
    public function run(): void
    {
        $this->command->info('🏢 Seeding test organizations...');

        $organizations = [
            [
                'name' => 'دیجی‌استور',
                'owner' => [
                    'name' => 'علی محمدی',
                    'email' => 'ali@digistore.com',
                    'phone' => '+989121234567',
                ],
            ],
            [
                'name' => 'تکنولایف مارکت',
                'owner' => [
                    'name' => 'مریم احمدی',
                    'email' => 'maryam@technolife.com',
                    'phone' => '+989132345678',
                ],
            ],
            [
                'name' => 'فشن‌لند',
                'owner' => [
                    'name' => 'سارا رضایی',
                    'email' => 'sara@fashionland.com',
                    'phone' => '+989143456789',
                ],
            ],
        ];

        foreach ($organizations as $orgData) {
            // Create owner user (buyer → will become store owner)
            $owner = User::firstOrCreate(
                ['email' => $orgData['owner']['email']],
                [
                    'name' => $orgData['owner']['name'],
                    'phone' => $orgData['owner']['phone'],
                    'password' => Hash::make('Store123!'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'status' => 'active',
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'password_expires_at' => now()->addDays(90),
                ]
            );

            // Create organization via service (triggers event → roles/permissions created)
            $org = $this->organizationService->createOrganization(
                ['name' => $orgData['name']],
                $owner
            );

            $this->command->line("   ✓ {$org->name} — Owner: {$owner->email} (ID: {$org->id})");
        }

        $this->command->info('✅ Test organizations seeded with owners!');
        $this->command->line('   Default password: Store123!');
    }
}
