<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignPermissionsToRoleAction
{
    /**
     * Assign permissions to a role
     */
    public function execute(Organization $organization, int $roleId, array $permissionNames): Role
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $role = Role::query()->where('id', $roleId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $oldPermissions = $role->permissions->pluck('name')->toArray();

        // Give permissions to role (adds without removing existing)
        $role->givePermissionTo($permissionNames);

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $newPermissions = $role->fresh()->permissions->pluck('name')->toArray();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties([
                'organization_id' => $organization->id,
                'role_name' => $role->name,
                'added_permissions' => array_diff($newPermissions, $oldPermissions),
            ])
            ->log('Permissions assigned to role');

        return $role->fresh(['permissions']);
    }
}
