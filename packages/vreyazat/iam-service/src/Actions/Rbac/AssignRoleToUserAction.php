<?php

namespace IamApi\Actions\Rbac;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignRoleToUserAction
{
    protected array $errors = [];

    /**
     * Assign a Spatie role to a user within an organization.
     *
     * Note: This assigns Spatie Permission roles (Layer 2 - RBAC).
     * For Organization roles (owner/admin/user/member), use UpdateMemberRoleAction.
     * 
     * @param Organization $organization
     * @param int $roleId Role ID (Spatie roles use integer IDs)
     * @param string $userUuid User UUID (Users use UUID for API)
     */
    public function execute(Organization $organization, int $roleId, string $userUuid): User
    {
        // Set the team context for Spatie Permission
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $role = Role::query()->where('id', $roleId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        /** @var User $user */
        $user = User::query()->where('uuid', $userUuid)->firstOrFail();

        // Check if user is member of the organization
        if ($user->organization_id !== $organization->id) {
            $this->addError('user', 'User is not a member of this organization.');
            return $user;
        }

        // Assign Spatie role to user (Layer 2 - RBAC)
        // Ensure team context is set before assignment
        $user->assignRole($role);

        // Verify organization_id was set in model_has_roles
        // If not, manually update it (workaround for Spatie team context issue)
        $hasOrgId = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_id', $user->id)
            ->where('model_type', User::class)
            ->where('organization_id', $organization->id)
            ->exists();

        if (!$hasOrgId) {
            // Update the pivot record to include organization_id
            DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->update(['organization_id' => $organization->id]);
        }

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
                'role_name' => $role->name,
                'role_type' => 'spatie_role', // Clarify this is Spatie role, not Organization role
            ])
            ->log('Spatie role assigned to user');

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
