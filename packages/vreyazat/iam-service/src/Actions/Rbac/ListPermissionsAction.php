<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ListPermissionsAction
{
    /**
     * List all permissions for an organization
     */
    public function execute(Organization $organization): array
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $permissions = Permission::query()->where('organization_id', $organization->id)
            ->withCount('roles', 'users')
            ->orderBy('name')
            ->get();

        return [
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'roles_count' => $permission->roles_count,
                    'users_count' => $permission->users_count,
                    'created_at' => $permission->created_at,
                    'updated_at' => $permission->updated_at,
                ];
            }),
            'total' => $permissions->count(),
        ];
    }
}
