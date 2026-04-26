<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class IamPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates all IAM built-in permissions.
     * These permissions are global (organization_id = NULL) and used for managing the IAM system itself.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding IAM built-in permissions...');

        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Set organization_id to NULL for global IAM permissions
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $iamPermissions = [
            // Organization Management
            ['name' => 'iam.org.view', 'description' => 'View organizations'],
            ['name' => 'iam.org.create', 'description' => 'Create organizations'],
            ['name' => 'iam.org.update', 'description' => 'Update organizations'],
            ['name' => 'iam.org.delete', 'description' => 'Delete organizations'],

            // User Management
            ['name' => 'iam.user.view', 'description' => 'View users'],
            ['name' => 'iam.user.create', 'description' => 'Create users'],
            ['name' => 'iam.user.update', 'description' => 'Update users'],
            ['name' => 'iam.user.delete', 'description' => 'Delete users'],
            ['name' => 'iam.user.lock', 'description' => 'Lock user accounts'],
            ['name' => 'iam.user.unlock', 'description' => 'Unlock user accounts'],

            // Member Management (Organization Members)
            ['name' => 'iam.member.view', 'description' => 'View organization members'],
            ['name' => 'iam.member.invite', 'description' => 'Invite new members'],
            ['name' => 'iam.member.add', 'description' => 'Add members to organization'],
            ['name' => 'iam.member.remove', 'description' => 'Remove members from organization'],
            ['name' => 'iam.member.update-role', 'description' => 'Update member roles'],

            // Invitation Management
            ['name' => 'iam.invitation.view', 'description' => 'View invitations'],
            ['name' => 'iam.invitation.create', 'description' => 'Create invitations'],
            ['name' => 'iam.invitation.resend', 'description' => 'Resend invitations'],
            ['name' => 'iam.invitation.cancel', 'description' => 'Cancel invitations'],

            // Role Management
            ['name' => 'iam.role.view', 'description' => 'View roles'],
            ['name' => 'iam.role.create', 'description' => 'Create roles'],
            ['name' => 'iam.role.update', 'description' => 'Update roles'],
            ['name' => 'iam.role.delete', 'description' => 'Delete roles'],
            ['name' => 'iam.role.assign', 'description' => 'Assign roles to users'],
            ['name' => 'iam.role.revoke', 'description' => 'Revoke roles from users'],

            // Permission Management
            ['name' => 'iam.permission.view', 'description' => 'View permissions'],
            ['name' => 'iam.permission.create', 'description' => 'Create permissions'],
            ['name' => 'iam.permission.update', 'description' => 'Update permissions'],
            ['name' => 'iam.permission.delete', 'description' => 'Delete permissions'],
            ['name' => 'iam.permission.assign', 'description' => 'Assign permissions to users/roles'],
            ['name' => 'iam.permission.revoke', 'description' => 'Revoke permissions from users/roles'],

            // Session Management
            ['name' => 'iam.session.view', 'description' => 'View user sessions'],
            ['name' => 'iam.session.revoke', 'description' => 'Revoke user sessions'],
            ['name' => 'iam.session.view-all', 'description' => 'View all user sessions (admin)'],

            // Security & Account Management
            ['name' => 'iam.account.lock', 'description' => 'Lock user accounts'],
            ['name' => 'iam.account.unlock', 'description' => 'Unlock user accounts'],
            ['name' => 'iam.mfa.manage', 'description' => 'Manage MFA for other users'],
            ['name' => 'iam.password.force-change', 'description' => 'Force password change for users'],

            // Audit & Monitoring
            ['name' => 'iam.audit.view', 'description' => 'View audit logs'],
            ['name' => 'iam.audit.export', 'description' => 'Export audit logs'],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($iamPermissions as $permData) {
            $exists = Permission::query()->where('name', $permData['name'])
                ->where('guard_name', 'api')
                ->whereNull('organization_id')
                ->exists();

            if (!$exists) {
                Permission::create([
                    'name' => $permData['name'],
                    'guard_name' => 'api',
                    'organization_id' => null, // Global IAM permissions
                ]);
                $this->command->line("   ✓ Created: {$permData['name']}");
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->command->info("✅ IAM Permissions seeded: $created created, $skipped skipped");
        $this->command->info("   Total IAM permissions: " . count($iamPermissions));

        // Reset cached permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
