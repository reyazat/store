<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class UpdateMemberRoleAction extends BaseAction
{
    public function __construct(
        protected OrganizationService $organizationService,
        protected UserRepositoryInterface $userRepository
    ) {}

    public function execute(array $data = []): array
    {
        /** @var Organization $organization */
        $organization = $data['organization'];

        /** @var User $currentUser */
        $currentUser = $data['user'];

        $targetUserUuid = $data['target_user_uuid'] ?? $data['target_user_id'] ?? null; // Support both for backward compatibility

        try {
            // Check permission
            if (!$this->organizationService->hasPermission($organization, $currentUser, 'users.manage')) {
                return $this->error('You do not have permission to update member roles', null, 403);
            }

            // Get target user by UUID
            /** @var User $targetUser */
            $targetUser = $this->userRepository->findByUuid($targetUserUuid);

            if (!$targetUser) {
                return $this->error('User not found', null, 404);
            }

            // Check if target user is a member
            if (!$this->organizationService->canUserAccess($organization, $targetUser)) {
                return $this->error('User is not a member of this organization');
            }

            // Prevent self-demotion if owner
            // Use UUID for comparison to maintain consistency with API layer
            if ($currentUser->uuid === $targetUser->uuid) {
                $currentRole = $this->organizationService->getUserRole($organization, $currentUser);
                if ($currentRole === 'owner' && $data['role'] !== 'owner') {
                    return $this->error('You cannot demote yourself from owner role');
                }
            }

            // In single-organization model, prevent demoting the last owner
            if ($data['role'] !== 'owner') {
                $currentRole = $this->organizationService->getUserRole($organization, $targetUser);
                if ($currentRole === 'owner') {
                    // Set team context for Spatie Permission
                    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
                    
                    $ownerCount = $organization->users()
                        ->whereHas('roles', function ($q) {
                            $q->where('roles.name', 'owner');
                        })
                        ->count();
                    
                    if ($ownerCount <= 1) {
                        return $this->error('Cannot demote the last owner from the organization', null, 400);
                    }
                }
            }

            // Update role via RBAC (not pivot table)
            $this->organizationService->updateMemberRole($organization, $targetUser, $data['role']);

            // Log role update
            activity()
                ->performedOn($organization)
                ->causedBy($currentUser)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'target_user_id' => $targetUser->id,
                    'new_role' => $data['role']
                ])
                ->log('Member role updated');

            return $this->success([
                'member' => [
                    'uuid' => $targetUser->uuid,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'role' => $data['role'],
                ]
            ], 'Member role updated successfully');

        } catch (Exception $e) {
            return $this->error('Failed to update member role: ' . $e->getMessage(), null, 500);
        }
    }
}
