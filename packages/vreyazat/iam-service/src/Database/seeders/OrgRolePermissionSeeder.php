<?php

namespace IamApi\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use IamApi\Models\Organization;

class OrgRolePermissionSeeder extends Seeder
{
    /**
     * Seed default organization with IAM permissions for Owner, Admin, User, Member roles.
     *
     * This creates a sample organization with predefined roles and IAM permissions.
     * Note: CMS-specific permissions should be registered by each CMS via API.
     */
    public function run(): void
    {
        $this->command->info('🏢 Seeding default organization roles and permissions...');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create or get default organization
        $org = Organization::query()->firstOrCreate(
            ['name' => 'Default Organization']
        );

        $this->command->line("   Organization: $org->name (ID: $org->id)");

        // Set organization context
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        // Create permissions for this organization
        // These permissions align with CreateDefaultOrganizationRoles and OrganizationService
        $iamOrgPermissions = [
            // User management (simplified naming without iam. prefix for organization-level)
            ['name' => 'users.view', 'description' => 'View users'],
            ['name' => 'users.manage', 'description' => 'Manage users (create, update, delete)'],
            ['name' => 'users.invite', 'description' => 'Invite new users'],
            
            // Content management
            ['name' => 'content.view', 'description' => 'View content'],
            ['name' => 'content.manage', 'description' => 'Manage content'],
            
            // Product management
            ['name' => 'products.view', 'description' => 'View products'],
            ['name' => 'products.manage', 'description' => 'Manage products (create, update, delete)'],

            // Order management
            ['name' => 'orders.view', 'description' => 'View orders'],
            ['name' => 'orders.manage', 'description' => 'Manage orders (update status, notes)'],

            // Reviews
            ['name' => 'reviews.manage', 'description' => 'Moderate reviews (approve, reject, reply)'],

            // Settings
            ['name' => 'settings.view', 'description' => 'View organization settings'],
            ['name' => 'settings.manage', 'description' => 'Manage organization settings'],
            
            // Domains
            ['name' => 'domains.view', 'description' => 'View domains'],
            ['name' => 'domains.manage', 'description' => 'Manage domains'],

            // Legacy IAM permissions (for backward compatibility)
            ['name' => 'iam.org.view', 'description' => 'View organization details'],
            ['name' => 'iam.org.update', 'description' => 'Update organization settings'],
            ['name' => 'iam.user.view', 'description' => 'View users in organization'],
            ['name' => 'iam.member.view', 'description' => 'View members'],
            ['name' => 'iam.role.view', 'description' => 'View roles'],
            ['name' => 'iam.permission.view', 'description' => 'View permissions'],
            ['name' => 'iam.audit.view', 'description' => 'View audit logs'],
        ];

        foreach ($iamOrgPermissions as $permData) {
            Permission::query()->firstOrCreate(
                ['name' => $permData['name'], 'guard_name' => 'api', 'organization_id' => $org->id]
            );
        }

        $this->command->line("   ✓ Created " . count($iamOrgPermissions) . " IAM permissions for organization");

        // Create roles: owner, admin, user, member
        $roles = ['owner', 'admin', 'user', 'member'];
        foreach ($roles as $roleName) {
            Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api',
                'organization_id' => $org->id
            ]);
        }

        $this->command->line("   ✓ Created roles: " . implode(', ', $roles));

        // Get roles
        $owner = Role::query()->where(['name' => 'owner', 'organization_id' => $org->id])->first();
        $admin = Role::query()->where(['name' => 'admin', 'organization_id' => $org->id])->first();
        $user = Role::query()->where(['name' => 'user', 'organization_id' => $org->id])->first();
        $member = Role::query()->where(['name' => 'member', 'organization_id' => $org->id])->first();

        // Owner: All permissions
        $allPermissions = Permission::query()->where('organization_id', $org->id)->pluck('name')->toArray();
        $owner->givePermissionTo($allPermissions);
        $this->command->line("   ✓ Owner: All permissions (" . count($allPermissions) . ")");

        // Admin: Most permissions except settings.manage and domains.manage
        $adminPermissions = [
            'users.view',
            'users.manage',
            'users.invite',
            'content.view',
            'content.manage',
            'products.view',
            'products.manage',
            'orders.view',
            'orders.manage',
            'reviews.manage',
            'settings.view',
            'domains.view',
            // Legacy IAM permissions
            'iam.org.view',
            'iam.user.view',
            'iam.member.view',
            'iam.role.view',
            'iam.permission.view',
            'iam.audit.view',
        ];
        $admin->givePermissionTo($adminPermissions);
        $this->command->line("   ✓ Admin: " . count($adminPermissions) . " permissions");

        // User: View-only + reviews management
        $userPermissions = [
            'users.view',
            'content.view',
            'products.view',
            'orders.view',
            'reviews.manage',
            'settings.view',
            // Legacy IAM permissions
            'iam.org.view',
            'iam.user.view',
            'iam.member.view',
            'iam.role.view',
            'iam.permission.view',
        ];
        $user->givePermissionTo($userPermissions);
        $this->command->line("   ✓ User: " . count($userPermissions) . " permissions");

        // Member: Minimal permissions
        $memberPermissions = [
            'users.view',
            'content.view',
            'products.view',
            // Legacy IAM permissions
            'iam.org.view',
        ];
        $member->givePermissionTo($memberPermissions);
        $this->command->line("   ✓ Member: " . count($memberPermissions) . " permissions");

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✅ Default organization roles and permissions seeded successfully!');
    }
}
