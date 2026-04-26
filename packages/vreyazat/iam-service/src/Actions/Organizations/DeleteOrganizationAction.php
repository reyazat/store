<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class DeleteOrganizationAction extends BaseAction
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
            // Check permission - only owners can delete
            $userRole = $this->organizationService->getUserRole($organization, $user);

            if ($userRole !== 'owner') {
                return $this->error('Only organization owners can delete the organization', null, 403);
            }

            // Log before deletion
            activity()
                ->performedOn($organization)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'organization_name' => $organization->name,
                ])
                ->log('Organization deleted');

            // Delete organization
            $deleted = $this->organizationRepository->delete($organization);

            if (!$deleted) {
                return $this->error('Failed to delete organization', null, 500);
            }

            return $this->success(null, 'Organization deleted successfully');

        } catch (Exception $e) {
            return $this->error('Failed to delete organization: ' . $e->getMessage(), null, 500);
        }
    }
}
