<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;
use Illuminate\Support\Facades\Auth;

class CreateOrganizationAction extends BaseAction
{
    public function __construct(
        protected OrganizationService $organizationService,
        protected UserRepositoryInterface $userRepository,
    ) {}

    public function execute(array $data = []): array
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return $this->error('User not authenticated', null, 401);
        }

        try {
            // Resolve the actual owner: use owner_id if provided (super admin flow), otherwise the authenticated user
            $owner = $user;
            if (!empty($data['owner_id'])) {
                $specified = $this->userRepository->findOneBy('id', $data['owner_id']);
                if (!$specified) {
                    return $this->error('Specified owner user not found', null, 404);
                }
                $owner = $specified;
            }

            $orgData = array_filter(
                array_intersect_key($data, array_flip(['name', 'description', 'logo', 'status'])),
                fn($v) => $v !== null
            );

            // Create organization and assign owner
            $organization = $this->organizationService->createOrganization($orgData, $owner);

            // Log organization creation
            activity()
                ->performedOn($organization)
                ->causedBy($user)
                ->withProperties([
                    'ip'         => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'owner_id'   => $owner->id,
                ])
                ->log('Organization created');

            return $this->success([
                'organization' => [
                    'uuid'        => $organization->uuid,
                    'name'        => $organization->name,
                    'description' => $organization->description,
                    'logo'        => $organization->logo,
                    'status'      => $organization->status,
                    'created_at'  => $organization->created_at,
                    'user_role'   => 'owner',
                ]
            ], 'Organization created successfully');

        } catch (Exception $e) {
            return $this->error('Failed to create organization: ' . $e->getMessage(), null, 500);
        }
    }
}
