<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RevokePermissionFromRoleAction
{
    /**
     * Revoke a permission from a role
     */
    public function execute(Organization $organization, int $roleId, int $permissionId): Role
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $role = Role::query()->where('id', $roleId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $permission = Permission::query()->where('id', $permissionId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        // Revoke permission from role
        $role->revokePermissionTo($permission);

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->withProperties([
                'organization_id' => $organization->id,
                'role_name' => $role->name,
                'permission_name' => $permission->name,
            ])
            ->log('Permission revoked from role');

        return $role->fresh(['permissions']);
    }
}
