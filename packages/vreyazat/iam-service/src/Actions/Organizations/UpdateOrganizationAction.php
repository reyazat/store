<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class UpdateOrganizationAction extends BaseAction
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
            // Check permission
            if (!$this->organizationService->hasPermission($organization, $user, 'settings.manage')) {
                return $this->error('You do not have permission to update this organization', null, 403);
            }

            // Prepare update data - only include explicitly provided allowed fields
            // Note: null values ARE allowed (to clear description/logo)
            $allowed    = ['name', 'description', 'logo', 'status'];
            $updateData = array_filter(
                $data,
                fn($key) => in_array($key, $allowed),
                ARRAY_FILTER_USE_KEY
            );

            if (empty($updateData)) {
                return $this->error('No valid fields to update', null, 400);
            }

            // Update organization
            $updated = $this->organizationRepository->update($organization, $updateData);

            if (!$updated) {
                return $this->error('Failed to update organization', null, 500);
            }

            // Log organization update
            activity()
                ->performedOn($organization)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'changes' => $updateData
                ])
                ->log('Organization updated');

            $organization->refresh();

            return $this->success([
                'organization' => [
                    'uuid'        => $organization->uuid,
                    'name'        => $organization->name,
                    'description' => $organization->description,
                    'logo'        => $organization->logo,
                    'status'      => $organization->status,
                ]
            ], 'Organization updated successfully');

        } catch (Exception $e) {
            return $this->error('Failed to update organization: ' . $e->getMessage(), null, 500);
        }
    }
}
