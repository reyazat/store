<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class RemoveMemberAction extends BaseAction
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
                return $this->error('You do not have permission to remove members', null, 403);
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

            // In single-organization model, prevent removing the last owner
            $userRole = $this->organizationService->getUserRole($organization, $targetUser);
            if ($userRole === 'owner') {
                // Set team context for Spatie Permission
                app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
                
                $ownerCount = $organization->users()
                    ->whereHas('roles', function ($q) {
                        $q->where('roles.name', 'owner');
                    })
                    ->count();
                
                if ($ownerCount <= 1) {
                    return $this->error('Cannot remove the last owner from the organization', null, 400);
                }
            }

            // Remove member by setting organization_id to NULL
            $this->organizationService->removeMember($organization, $targetUser);

            // Log member removal
            activity()
                ->performedOn($organization)
                ->causedBy($currentUser)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'removed_user_id' => $targetUser->id,
                ])
                ->log('Member removed from organization');

            return $this->success(null, 'Member removed successfully');

        } catch (Exception $e) {
            return $this->error('Failed to remove member: ' . $e->getMessage(), null, 500);
        }
    }
}
