<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DeletePermissionAction
{
    protected array $errors = [];

    /**
     * Delete a permission
     */
    public function execute(Organization $organization, int $permissionId): bool
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $permission = Permission::query()->where('id', $permissionId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $permissionName = $permission->name;
        $rolesCount = $permission->roles()->count();
        $usersCount = $permission->users()->count();

        // Check if permission is assigned
        if ($rolesCount > 0 || $usersCount > 0) {
            $this->addError('permission', "Cannot delete permission '$permissionName' because it is assigned to $rolesCount role(s) and $usersCount user(s).");
            return false;
        }

        // Delete the permission
        $permission->delete();

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'organization_id' => $organization->id,
                'permission_name' => $permissionName,
            ])
            ->log('Permission deleted');

        return true;
    }

    protected function addError(string $key, string $message): void
    {
        $this->errors[$key][] = $message;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
