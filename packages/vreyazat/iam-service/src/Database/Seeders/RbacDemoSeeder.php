<?php

namespace IamApi\Database\Seeders;

use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * RBAC Demo Seeder
 * 
 * Creates a dedicated demo organization with users in all roles for testing RBAC endpoints.
 * Uses OrganizationService for proper Layer 1 + Layer 2 sync.
 * 
 * Usage:
 * php artisan db:seed --class=RbacDemoSeeder
 * 
 * Test Users:
 * - owner@demo.com / Demo123! (Owner role with all permissions)
 * - admin@demo.com / Demo123! (Admin role with most permissions)
 * - editor@demo.com / Demo123! (Editor role with content permissions)
 * - viewer@demo.com / Demo123! (Member role with minimal permissions)
 */
class RbacDemoSeeder extends Seeder
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    public function run(): void
    {
        $this->command->info('🎭 Seeding RBAC Demo Data...');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Use the configured auth User model (e.g. App\Models\User) so that Spatie
        // stores the correct model_type in model_has_roles. Falls back to IamApi\Models\User.
        $userClass = config('auth.providers.users.model', User::class);

        // Create owner user first
        $ownerUser = $userClass::firstOrCreate(
            ['email' => 'owner@demo.com'],
            [
                'name' => 'Demo Owner',
                'phone' => '+989191234001',
                'password' => Hash::make('Demo123!'),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'status' => 'active',
                'must_change_password' => false,
                'password_changed_at' => now(),
                'password_expires_at' => now()->addDays(90),
            ]
        );

        // Create organization via service → triggers OrganizationCreated event
        // This auto-creates default roles (owner, admin, editor, user, member) and permissions
        $demoOrg = $this->organizationService->createOrganization(
            ['name' => 'Demo Company'],
            $ownerUser
        );

        $this->command->line("   ✓ Organization: {$demoOrg->name} (ID: {$demoOrg->id})");
        $this->command->line("   ✓ Owner: owner@demo.com → assigned with all permissions");

        // Create additional users and assign to organization via service
        $members = [
            [
                'name' => 'Demo Admin',
                'email' => 'admin@demo.com',
                'phone' => '+989191234002',
                'role' => 'admin',
            ],
            [
                'name' => 'Demo Editor',
                'email' => 'editor@demo.com',
                'phone' => '+989191234003',
                'role' => 'editor',
            ],
            [
                'name' => 'Demo Viewer',
                'email' => 'viewer@demo.com',
                'phone' => '+989191234004',
                'role' => 'member',
            ],
        ];

        foreach ($members as $memberData) {
            $user = $userClass::firstOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'phone' => $memberData['phone'],
                    'password' => Hash::make('Demo123!'),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'status' => 'active',
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'password_expires_at' => now()->addDays(90),
                ]
            );

            // Add member via service (Layer 1 + Layer 2 sync)
            $this->organizationService->addMember($demoOrg, $user, $memberData['role']);

            $this->command->line("   ✓ {$memberData['role']}: {$memberData['email']}");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✅ RBAC Demo Data seeded successfully!');
        $this->command->newLine();
        $this->command->info('🔐 Test Credentials (password: Demo123!):');
        $this->command->table(
            ['Email', 'Role', 'Organization'],
            [
                ['owner@demo.com', 'owner', $demoOrg->name],
                ['admin@demo.com', 'admin', $demoOrg->name],
                ['editor@demo.com', 'editor', $demoOrg->name],
                ['viewer@demo.com', 'member', $demoOrg->name],
            ]
        );
    }
}
