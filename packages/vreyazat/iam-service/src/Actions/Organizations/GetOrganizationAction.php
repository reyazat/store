<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;
use Exception;

class GetOrganizationAction extends BaseAction
{
    public function __construct(
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

            $userRole = $this->organizationService->getUserRole($organization, $user);
            $statistics = $this->organizationService->getStatistics($organization);

            return $this->success([
                'organization' => [
                    'uuid' => $organization->uuid,
                    'name' => $organization->name,
                    'created_at' => $organization->created_at,
                    'user_role' => $userRole,
                    'statistics' => $statistics,
                ]
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to fetch organization: ' . $e->getMessage(), null, 500);
        }
    }
}
