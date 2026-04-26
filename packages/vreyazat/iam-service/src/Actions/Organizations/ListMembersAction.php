<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class ListMembersAction extends BaseAction
{
    public function __construct(
        protected OrganizationRepositoryInterface $organizationRepository,
        protected OrganizationService $organizationService
    ) {}

    public function execute(array $data = []): array
    {
        /** @var Organization $organization */
        $organization = $data['organization'];

        /** @var User $user */
        $user = $data['user'];

        try {
            // Check if user can access organization
            if (!$this->organizationService->canUserAccess($organization, $user)) {
                return $this->error('You do not have access to this organization', null, 403);
            }

            // In single-organization model, get all users belonging to this organization
            $members = $organization->users()->get();

            $membersData = $members->map(function ($member) use ($organization) {
                // Get user's role in this organization via RBAC
                $role = $this->organizationService->getUserRole($organization, $member);
                
                return [
                    'uuid' => $member->uuid,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $role,
                    'joined_at' => $member->created_at,
                ];
            });

            return $this->success([
                'members' => $membersData,
                'total' => $membersData->count(),
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to fetch members: ' . $e->getMessage(), null, 500);
        }
    }
}
