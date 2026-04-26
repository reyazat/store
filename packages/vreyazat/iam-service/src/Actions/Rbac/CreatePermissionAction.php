<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CreatePermissionAction
{
    /**
     * Create a new permission for an organization
     */
    public function execute(Organization $organization, array $data): Permission
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        // Create the permission
        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'api',
            'organization_id' => $organization->id,
        ]);

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->withProperties([
                'organization_id' => $organization->id,
                'permission_name' => $permission->name,
            ])
            ->log('Permission created');

        return $permission;
    }
}
