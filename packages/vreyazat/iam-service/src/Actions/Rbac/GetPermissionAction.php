<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class GetPermissionAction
{
    /**
     * Get permission details with roles and users
     */
    public function execute(Organization $organization, int $permissionId): Permission
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        return Permission::query()->where('id', $permissionId)
            ->where('organization_id', $organization->id)
            ->with(['roles', 'users'])
            ->firstOrFail();
    }
}
