<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AssignPermissionToUserAction
{
    protected array $errors = [];

    /**
     * Assign a permission directly to a user within an organization
     * 
     * @param Organization $organization
     * @param int $permissionId Permission ID (Spatie permissions use integer IDs)
     * @param string $userUuid User UUID (Users use UUID for API)
     */
    public function execute(Organization $organization, int $permissionId, string $userUuid): User
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $permission = Permission::query()->where('id', $permissionId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        /** @var User $user */
        $user = User::query()->where('uuid', $userUuid)->firstOrFail();

        // Check if user is member of the organization
        if ($user->organization_id !== $organization->id) {
            $this->addError('user', 'User is not a member of this organization.');
            return $user;
        }

        // Assign permission to user
        $user->givePermissionTo($permission);

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
                'permission_name' => $permission->name,
            ])
            ->log('Permission assigned to user');

        return $user->fresh(['roles', 'permissions']);
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
