<?php

namespace Database\Seeders;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RBAC Demo Seeder
 * 
 * این سیدر برای تست endpoint های RBAC طراحی شده است.
 * کاربران با نقش‌ها و دسترسی‌های مختلف در سازمان‌های مختلف ایجاد می‌کند.
 * 
 * Usage:
 * php artisan db:seed --class=RbacDemoSeeder
 * 
 * Test Users:
 * - owner@demo.com / Demo123! (Owner role with all permissions)
 * - admin@demo.com / Demo123! (Admin role with most permissions)
 * - editor@demo.com / Demo123! (Editor role with content permissions)
 * - viewer@demo.com / Demo123! (Viewer role with read-only permissions)
 */
class RbacDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎭 Seeding RBAC Demo Data...');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create Demo Organization
        $demoOrg = Organization::firstOrCreate(
            ['name' => 'Demo Company'],
        );

        $this->command->line("   📦 Organization: {$demoOrg->name} (ID: {$demoOrg->id})");

        // Set organization context
        app(PermissionRegistrar::class)->setPermissionsTeamId($demoOrg->id);

        // Create comprehensive permissions
        $permissions = $this->createPermissions($demoOrg);
        $this->command->line("   ✓ Created {$permissions->count()} permissions");

        // Create roles with different permission sets
        $roles = $this->createRoles($demoOrg, $permissions);
        $this->command->line("   ✓ Created " . count($roles) . " roles");

        // Create demo users and assign to organization with roles
        $users = $this->createUsers($demoOrg, $roles);
        $this->command->line("   ✓ Created " . count($users) . " demo users");

        // Assign direct permissions to some users
        $this->assignDirectPermissions($users, $permissions, $demoOrg);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✅ RBAC Demo Data seeded successfully!');
        $this->command->newLine();
        $this->command->info('🔐 Test Credentials:');
        $this->command->table(
            ['Email', 'Password', 'Role', 'Organization ID'],
            [
                ['owner@demo.com', 'Demo123!', 'owner', $demoOrg->id],
                ['admin@demo.com', 'Demo123!', 'admin', $demoOrg->id],
                ['editor@demo.com', 'Demo123!', 'editor', $demoOrg->id],
                ['viewer@demo.com', 'Demo123!', 'viewer', $demoOrg->id],
            ]
        );
        $this->command->newLine();
        $this->command->info('📝 Test Endpoint:');
        $this->command->line("   GET /api/v1/auth/me/permissions?organization_id={$demoOrg->id}");
        $this->command->line("   GET /api/v1/auth/me/roles?organization_id={$demoOrg->id}");
    }

    private function createPermissions(Organization $org)
    {
        $permissionsList = [
            // IAM Permissions
            ['name' => 'iam.org.view', 'description' => 'View organization details'],
            ['name' => 'iam.org.update', 'description' => 'Update organization settings'],
            ['name' => 'iam.org.delete', 'description' => 'Delete organization'],
            
            ['name' => 'iam.user.view', 'description' => 'View users'],
            ['name' => 'iam.user.create', 'description' => 'Create users'],
            ['name' => 'iam.user.update', 'description' => 'Update users'],
            ['name' => 'iam.user.delete', 'description' => 'Delete users'],
            
            ['name' => 'iam.member.view', 'description' => 'View members'],
            ['name' => 'iam.member.invite', 'description' => 'Invite members'],
            ['name' => 'iam.member.add', 'description' => 'Add members'],
            ['name' => 'iam.member.remove', 'description' => 'Remove members'],
            ['name' => 'iam.member.update-role', 'description' => 'Update member roles'],
            
            ['name' => 'iam.role.view', 'description' => 'View roles'],
            ['name' => 'iam.role.create', 'description' => 'Create roles'],
            ['name' => 'iam.role.update', 'description' => 'Update roles'],
            ['name' => 'iam.role.delete', 'description' => 'Delete roles'],
            ['name' => 'iam.role.assign', 'description' => 'Assign roles to users'],
            ['name' => 'iam.role.revoke', 'description' => 'Revoke roles from users'],
            
            ['name' => 'iam.permission.view', 'description' => 'View permissions'],
            ['name' => 'iam.permission.create', 'description' => 'Create permissions'],
            ['name' => 'iam.permission.update', 'description' => 'Update permissions'],
            ['name' => 'iam.permission.delete', 'description' => 'Delete permissions'],
            ['name' => 'iam.permission.assign', 'description' => 'Assign permissions'],
            ['name' => 'iam.permission.revoke', 'description' => 'Revoke permissions'],
            
            ['name' => 'iam.audit.view', 'description' => 'View audit logs'],
            ['name' => 'iam.session.view', 'description' => 'View sessions'],
            ['name' => 'iam.session.revoke', 'description' => 'Revoke sessions'],

            // CMS Content Permissions (Example)
            ['name' => 'content.article.view', 'description' => 'View articles'],
            ['name' => 'content.article.create', 'description' => 'Create articles'],
            ['name' => 'content.article.edit', 'description' => 'Edit articles'],
            ['name' => 'content.article.delete', 'description' => 'Delete articles'],
            ['name' => 'content.article.publish', 'description' => 'Publish articles'],
            
            ['name' => 'content.page.view', 'description' => 'View pages'],
            ['name' => 'content.page.create', 'description' => 'Create pages'],
            ['name' => 'content.page.edit', 'description' => 'Edit pages'],
            ['name' => 'content.page.delete', 'description' => 'Delete pages'],
            
            ['name' => 'content.media.view', 'description' => 'View media files'],
            ['name' => 'content.media.upload', 'description' => 'Upload media files'],
            ['name' => 'content.media.delete', 'description' => 'Delete media files'],
            
            ['name' => 'content.comment.view', 'description' => 'View comments'],
            ['name' => 'content.comment.moderate', 'description' => 'Moderate comments'],
            ['name' => 'content.comment.delete', 'description' => 'Delete comments'],

            // Settings Permissions
            ['name' => 'settings.general.view', 'description' => 'View general settings'],
            ['name' => 'settings.general.update', 'description' => 'Update general settings'],
            ['name' => 'settings.advanced.view', 'description' => 'View advanced settings'],
            ['name' => 'settings.advanced.update', 'description' => 'Update advanced settings'],
        ];

        $permissions = collect();
        foreach ($permissionsList as $permData) {
            $permission = Permission::firstOrCreate(
                [
                    'name' => $permData['name'],
                    'guard_name' => 'api',
                    'organization_id' => $org->id
                ]
            );
            $permissions->push($permission);
        }

        return $permissions;
    }

    private function createRoles(Organization $org, $permissions)
    {
        // Owner Role - All permissions
        $ownerRole = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'api',
            'organization_id' => $org->id
        ]);
        $ownerRole->syncPermissions($permissions->pluck('name')->toArray());

        // Admin Role - Most permissions except organization deletion
        $adminPermissions = $permissions->filter(function ($perm) {
            return !in_array($perm->name, ['iam.org.delete']);
        });
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
            'organization_id' => $org->id
        ]);
        $adminRole->syncPermissions($adminPermissions->pluck('name')->toArray());

        // Editor Role - Content management permissions
        $editorPermissions = $permissions->filter(function ($perm) {
            return str_starts_with($perm->name, 'content.') ||
                   in_array($perm->name, [
                       'iam.org.view',
                       'iam.user.view',
                       'iam.member.view',
                   ]);
        });
        $editorRole = Role::firstOrCreate([
            'name' => 'editor',
            'guard_name' => 'api',
            'organization_id' => $org->id
        ]);
        $editorRole->syncPermissions($editorPermissions->pluck('name')->toArray());

        // Viewer Role - Read-only permissions
        $viewerPermissions = $permissions->filter(function ($perm) {
            return str_ends_with($perm->name, '.view');
        });
        $viewerRole = Role::firstOrCreate([
            'name' => 'viewer',
            'guard_name' => 'api',
            'organization_id' => $org->id
        ]);
        $viewerRole->syncPermissions($viewerPermissions->pluck('name')->toArray());

        return [
            'owner' => $ownerRole,
            'admin' => $adminRole,
            'editor' => $editorRole,
            'viewer' => $viewerRole,
        ];
    }

    private function createUsers(Organization $org, array $roles)
    {
        $usersData = [
            [
                'name' => 'Owner User',
                'email' => 'owner@demo.com',
                'password' => 'Demo123!',
                'role' => 'owner',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@demo.com',
                'password' => 'Demo123!',
                'role' => 'admin',
            ],
            [
                'name' => 'Editor User',
                'email' => 'editor@demo.com',
                'password' => 'Demo123!',
                'role' => 'editor',
            ],
            [
                'name' => 'Viewer User',
                'email' => 'viewer@demo.com',
                'password' => 'Demo123!',
                'role' => 'viewer',
            ],
        ];

        $users = [];
        foreach ($usersData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'password_expires_at' => now()->addDays(90),
                ]
            );

            // User is already assigned to organization via organization_id in factory

            // Assign role to user in organization context
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            $user->assignRole($roles[$userData['role']]);

            $users[$userData['email']] = $user;

            $this->command->line("      - {$userData['name']} ({$userData['email']}) - Role: {$userData['role']}");
        }

        return $users;
    }

    private function assignDirectPermissions(array $users, $permissions, Organization $org)
    {
        // Set organization context
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        // Give editor user a direct permission (in addition to role permissions)
        if (isset($users['editor@demo.com'])) {
            $editorUser = $users['editor@demo.com'];
            $publishPermission = $permissions->firstWhere('name', 'content.article.publish');
            if ($publishPermission) {
                $editorUser->givePermissionTo($publishPermission);
                $this->command->line("      - Direct permission 'content.article.publish' assigned to editor@demo.com");
            }
        }

        // Give viewer user a direct permission to moderate comments
        if (isset($users['viewer@demo.com'])) {
            $viewerUser = $users['viewer@demo.com'];
            $moderatePermission = $permissions->firstWhere('name', 'content.comment.moderate');
            if ($moderatePermission) {
                $viewerUser->givePermissionTo($moderatePermission);
                $this->command->line("      - Direct permission 'content.comment.moderate' assigned to viewer@demo.com");
            }
        }
    }
}
