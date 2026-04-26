<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;
use Exception;
use Illuminate\Support\Facades\Auth;

class CreateOrganizationAction extends BaseAction
{
    public function __construct(
        protected OrganizationService $organizationService
    ) {}

    public function execute(array $data = []): array
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return $this->error('User not authenticated', null, 401);
        }

        try {
            // Create organization
            $organization = $this->organizationService->createOrganization($data, $user);

            // Log organization creation
            activity()
                ->performedOn($organization)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('Organization created');

            return $this->success([
                'organization' => [
                    'uuid' => $organization->uuid,
                    'name' => $organization->name,
                    'created_at' => $organization->created_at,
                    'user_role' => 'owner'
                ]
            ], 'Organization created successfully');

        } catch (Exception $e) {
            return $this->error('Failed to create organization: ' . $e->getMessage(), null, 500);
        }
    }
}
