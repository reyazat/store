<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use Exception;

class ListOrganizationsAction extends BaseAction
{
    public function __construct(
        protected OrganizationRepositoryInterface $organizationRepository
    ) {}

    public function execute(array $data = []): array
    {
        /** @var User $user */
        $user = $data['user'];

        try {
            // In single-organization model, each user has exactly one organization
            if (!$user->organization) {
                return $this->success([
                    'organizations' => []
                ]);
            }

            $organization = $user->organization;

            $organizationData = [
                'uuid' => $organization->uuid,
                'name' => $organization->name,
                'created_at' => $organization->created_at,
                'updated_at' => $organization->updated_at,
                'user_role' => $this->organizationRepository->getUserRole($organization, $user),
                'members_count' => $this->organizationRepository->countMembers($organization),
            ];

            return $this->success([
                'organizations' => [$organizationData]
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to fetch organizations: ' . $e->getMessage(), null, 500);
        }
    }
}
