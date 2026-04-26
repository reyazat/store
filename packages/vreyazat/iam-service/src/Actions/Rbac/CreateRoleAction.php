<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateRoleAction
{
    /**
     * Create a new role for an organization
     */
    public function execute(Organization $organization, array $data): Role
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        // Create the role
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'api',
            'organization_id' => $organization->id,
        ]);

        // Assign permissions if provided
        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties([
                'organization_id' => $organization->id,
                'role_name' => $role->name,
                'permissions' => $data['permissions'] ?? [],
            ])
            ->log('Role created');

        return $role->load('permissions');
    }
}
