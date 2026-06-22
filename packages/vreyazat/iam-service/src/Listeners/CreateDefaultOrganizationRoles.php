<?php

namespace IamApi\Listeners;

use IamApi\Events\OrganizationCreated;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateDefaultOrganizationRoles
{
    /**
     * Handle the event.
     * 
     * This listener creates default roles and permissions for each new organization.
     * These are Layer 2 (Spatie Permission) roles that work alongside Layer 1 (Organization roles).
     */
    public function handle(OrganizationCreated $event): void
    {
        $organization = $event->organization;

        // Set organization context for team-based permissions
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        // Define default permissions for this organization
        $defaultPermissions = [
            // User management
            'users.view' => 'View users',
            'users.manage' => 'Manage users (invite, add, remove)',
            'users.invite' => 'Invite new users',
            
            // Content management
            'content.view' => 'View content',
            'content.manage' => 'Manage content',
            
            // Product management
            'products.view' => 'View products',
            'products.manage' => 'Manage products (create, update, delete)',
            
            // Order management
            'orders.view' => 'View orders',
            'orders.manage' => 'Manage orders (update status, notes)',
            
            // Settings
            'settings.view' => 'View organization settings',
            'settings.manage' => 'Manage organization settings',
            
            // Domains
            'domains.view' => 'View domains',
            'domains.manage' => 'Manage domains',
        ];

        // Create permissions
        $createdPermissions = [];
        foreach ($defaultPermissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'api',
                    'organization_id' => $organization->id,
                ]
            );
            $createdPermissions[$name] = $permission;
        }

        // Define default roles with their permissions
        $defaultRoles = [
            'owner' => [
                'permissions' => array_keys($defaultPermissions), // All permissions
                'description' => 'Organization owner with full access',
            ],
            'admin' => [
                'permissions' => [
                    'users.view',
                    'users.manage',
                    'users.invite',
                    'content.view',
                    'content.manage',
                    'products.view',
                    'products.manage',
                    'orders.view',
                    'orders.manage',
                    'settings.view',
                    'settings.manage',
                    'domains.view',
                ],
                'description' => 'Administrator with most permissions',
            ],
            'editor' => [
                'permissions' => [
                    'users.view',
                    'content.view',
                    'content.manage',
                    'products.view',
                    'products.manage',
                    'orders.view',
                ],
                'description' => 'Editor can manage content and products',
            ],
            'user' => [
                'permissions' => [
                    'users.view',
                    'content.view',
                    'products.view',
                    'orders.view',
                    'settings.view',
                ],
                'description' => 'Regular user with view-only access',
            ],
            'member' => [
                'permissions' => [
                    'users.view',
                    'content.view',
                    'products.view',
                ],
                'description' => 'Basic member with minimal access',
            ],
        ];

        // Create roles and assign permissions
        foreach ($defaultRoles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'api',
                    'organization_id' => $organization->id,
                ]
            );

            // Assign permissions to role
            $permissionsToAssign = [];
            foreach ($roleData['permissions'] as $permissionName) {
                if (isset($createdPermissions[$permissionName])) {
                    $permissionsToAssign[] = $createdPermissions[$permissionName];
                }
            }
            
            if (!empty($permissionsToAssign)) {
                $role->syncPermissions($permissionsToAssign);
            }
        }

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        \Log::info("Default roles and permissions created for organization {$organization->id}");
    }
}
