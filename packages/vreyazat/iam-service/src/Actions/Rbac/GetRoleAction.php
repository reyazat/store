<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GetRoleAction
{
    /**
     * Get role details with permissions and users
     */
    public function execute(Organization $organization, int $roleId): Role
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        return Role::query()->where('id', $roleId)
            ->where('organization_id', $organization->id)
            ->with(['permissions', 'users'])
            ->firstOrFail();
    }
}
