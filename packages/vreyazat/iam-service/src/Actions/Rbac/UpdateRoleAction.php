<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UpdateRoleAction
{
    /**
     * Update a role
     */
    public function execute(Organization $organization, int $roleId, array $data): Role
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $role = Role::query()->where('id', $roleId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $oldData = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        // Update role name if provided
        if (isset($data['name'])) {
            $role->name = $data['name'];
            $role->save();
        }

        // Update permissions if provided
        if (isset($data['permissions'])) {
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
                'old' => $oldData,
                'new' => [
                    'name' => $role->name,
                    'permissions' => $data['permissions'] ?? $oldData['permissions'],
                ],
            ])
            ->log('Role updated');

        return $role->fresh(['permissions']);
    }
}
