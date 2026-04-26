<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RevokeRoleFromUserAction
{
    /**
     * Revoke a role from a user within an organization
     * 
     * @param Organization $organization
     * @param int $roleId Role ID (Spatie roles use integer IDs)
     * @param string $userUuid User UUID (Users use UUID for API)
     */
    public function execute(Organization $organization, int $roleId, string $userUuid): User
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $role = Role::query()->where('id', $roleId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $user = User::query()->where('uuid', $userUuid)->firstOrFail();

        // Revoke role from user
        $user->removeRole($role);

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
                'role_name' => $role->name,
            ])
            ->log('Role revoked from user');

        return $user->fresh(['roles', 'permissions']);
    }
}
