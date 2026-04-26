<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ListRolesAction
{
    /**
     * List all roles for an organization
     */
    public function execute(Organization $organization): array
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $roles = Role::query()->where('organization_id', $organization->id)
            ->withCount('permissions', 'users')
            ->orderBy('name')
            ->get();

        return [
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ];
            }),
            'total' => $roles->count(),
        ];
    }
}
