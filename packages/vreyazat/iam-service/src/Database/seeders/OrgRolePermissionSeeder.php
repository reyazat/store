<?php

namespace Database\Seeders;

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

        // Create IAM permissions for this organization
        // These are the permissions that control IAM features within the organization
        $iamOrgPermissions = [
            // Organization management (only for this org)
            ['name' => 'iam.org.view', 'description' => 'View organization details'],
            ['name' => 'iam.org.update', 'description' => 'Update organization settings'],

            // User management within organization
            ['name' => 'iam.user.view', 'description' => 'View users in organization'],
            ['name' => 'iam.user.create', 'description' => 'Create users in organization'],
            ['name' => 'iam.user.update', 'description' => 'Update users in organization'],
            ['name' => 'iam.user.delete', 'description' => 'Deleted users from organization'],

            // Member management
            ['name' => 'iam.member.view', 'description' => 'View members'],
            ['name' => 'iam.member.invite', 'description' => 'Invite new members'],
            ['name' => 'iam.member.add', 'description' => 'Add members'],
            ['name' => 'iam.member.remove', 'description' => 'Remove members'],
            ['name' => 'iam.member.update-role', 'description' => 'Update member roles'],

            // Role management
            ['name' => 'iam.role.view', 'description' => 'View roles'],
            ['name' => 'iam.role.create', 'description' => 'Create roles'],
            ['name' => 'iam.role.update', 'description' => 'Update roles'],
            ['name' => 'iam.role.delete', 'description' => 'Delete roles'],
            ['name' => 'iam.role.assign', 'description' => 'Assign roles'],
            ['name' => 'iam.role.revoke', 'description' => 'Revoke roles'],

            // Permission management
            ['name' => 'iam.permission.view', 'description' => 'View permissions'],
            ['name' => 'iam.permission.create', 'description' => 'Create permissions'],
            ['name' => 'iam.permission.update', 'description' => 'Update permissions'],
            ['name' => 'iam.permission.delete', 'description' => 'Delete permissions'],
            ['name' => 'iam.permission.assign', 'description' => 'Assign permissions'],
            ['name' => 'iam.permission.revoke', 'description' => 'Revoke permissions'],

            // Audit
            ['name' => 'iam.audit.view', 'description' => 'View audit logs'],

            // Session management
            ['name' => 'iam.session.view', 'description' => 'View sessions'],
            ['name' => 'iam.session.revoke', 'description' => 'Revoke sessions'],
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

        // Owner: All IAM permissions
        $allPermissions = Permission::query()->where('organization_id', $org->id)->pluck('name')->toArray();
        $owner->givePermissionTo($allPermissions);
        $this->command->line("   ✓ Owner: All permissions (" . count($allPermissions) . ")");

        // Admin: Most permissions except role/permission management
        $adminPermissions = [
            'iam.org.view',
            'iam.user.view', 'iam.user.create', 'iam.user.update', 'iam.user.delete',
            'iam.member.view', 'iam.member.invite', 'iam.member.add', 'iam.member.remove',
            'iam.role.view', 'iam.role.assign',
            'iam.permission.view',
            'iam.audit.view',
            'iam.session.view', 'iam.session.revoke',
        ];
        $admin->givePermissionTo($adminPermissions);
        $this->command->line("   ✓ Admin: " . count($adminPermissions) . " permissions");

        // User: Limited permissions (can view, cannot manage)
        $userPermissions = [
            'iam.org.view',
            'iam.user.view',
            'iam.member.view',
            'iam.role.view',
            'iam.permission.view',
        ];
        $user->givePermissionTo($userPermissions);
        $this->command->line("   ✓ User: " . count($userPermissions) . " permissions");

        // Member: Minimal permissions (only view own data)
        $memberPermissions = [
            'iam.org.view',
        ];
        $member->givePermissionTo($memberPermissions);
        $this->command->line("   ✓ Member: " . count($memberPermissions) . " permissions");

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✅ Default organization roles and permissions seeded successfully!');
    }
}
