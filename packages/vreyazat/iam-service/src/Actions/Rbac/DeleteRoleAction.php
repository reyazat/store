<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DeleteRoleAction
{
    protected array $errors = [];

    /**
     * Delete a role
     */
    public function execute(Organization $organization, int $roleId): bool
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $role = Role::query()->where('id', $roleId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $roleName = $role->name;
        $usersCount = $role->users()->count();

        // Check if role is assigned to users
        if ($usersCount > 0) {
            $this->addError('role', "Cannot delete role '$roleName' because it is assigned to $usersCount user(s).");
            return false;
        }

        // Delete the role
        $role->delete();

        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'organization_id' => $organization->id,
                'role_name' => $roleName,
            ])
            ->log('Role deleted');

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
