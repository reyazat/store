<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UpdatePermissionAction
{
    /**
     * Update a permission
     */
    public function execute(Organization $organization, int $permissionId, array $data): Permission
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $permission = Permission::query()->where('id', $permissionId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $oldName = $permission->name;

        // Update permission name if provided
        if (isset($data['name'])) {
            $permission->name = $data['name'];
            $permission->save();
        }

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->withProperties([
                'organization_id' => $organization->id,
                'old_name' => $oldName,
                'new_name' => $permission->name,
            ])
            ->log('Permission updated');

        return $permission->fresh();
    }
}
