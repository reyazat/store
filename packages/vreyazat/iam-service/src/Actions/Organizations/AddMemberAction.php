<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class AddMemberAction extends BaseAction
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

        try {
            // Check permission
            if (!$this->organizationService->hasPermission($organization, $currentUser, 'users.manage')) {
                return $this->error('You do not have permission to add members', null, 403);
            }

            // Find or get user
            if (isset($data['user_uuid'])) {
                $targetUser = $this->userRepository->findOneBy('uuid', $data['user_uuid']);
                if (!$targetUser) {
                    return $this->error('User not found with this UUID', null, 404);
                }
            } else {
                $targetUser = $this->userRepository->findByEmail($data['email']);

                if (!$targetUser) {
                    return $this->error('User not found with this email', null, 404);
                }
            }

            // Check if already a member
            if ($this->organizationService->canUserAccess($organization, $targetUser)) {
                return $this->error('User is already a member of this organization');
            }

            // In single-organization model, check if user already has an organization
            if ($targetUser->organization_id) {
                return $this->error('User is already assigned to another organization. Cannot add to multiple organizations.', null, 400);
            }

            // Add member by assigning organization_id
            $this->organizationService->addMember($organization, $targetUser, $data['role']);

            // Log member addition
            activity()
                ->performedOn($organization)
                ->causedBy($currentUser)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'added_user_id' => $targetUser->id,
                    'role' => $data['role']
                ])
                ->log('Member added to organization');

            return $this->success([
                'member' => [
                    'uuid' => $targetUser->uuid,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'role' => $data['role'],
                ]
            ], 'Member added successfully');

        } catch (Exception $e) {
            return $this->error('Failed to add member: ' . $e->getMessage(), null, 500);
        }
    }
}
