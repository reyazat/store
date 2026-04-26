<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RevokePermissionFromUserAction
{
    /**
     * Revoke a permission from a user within an organization
     * 
     * @param Organization $organization
     * @param int $permissionId Permission ID (Spatie permissions use integer IDs)
     * @param string $userUuid User UUID (Users use UUID for API)
     */
    public function execute(Organization $organization, int $permissionId, string $userUuid): User
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $permission = Permission::query()->where('id', $permissionId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $user = User::query()->where('uuid', $userUuid)->firstOrFail();

        // Revoke permission from user
        $user->revokePermissionTo($permission);

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'permission_name' => $permission->name,
            ])
            ->log('Permission revoked from user');

        return $user->fresh(['roles', 'permissions']);
    }
}
