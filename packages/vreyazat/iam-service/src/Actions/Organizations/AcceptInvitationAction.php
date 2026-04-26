<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;
use Exception;
use Illuminate\Support\Facades\Auth;

class AcceptInvitationAction extends BaseAction
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
            // In single-organization model, user can only have one organization
            if ($user->organization_id) {
                return $this->error('User is already assigned to an organization. Cannot accept another invitation.', null, 400);
            }

            // Accept invitation and assign organization to user
            $accepted = $this->organizationService->acceptInvitation($data['token'], $user);

            if (!$accepted) {
                return $this->error('Failed to accept invitation', null, 500);
            }

            // Refresh user to get updated organization
            $user->refresh();

            // Log invitation acceptance
            activity()
                ->causedBy($user)
                ->withProperties([
                    'token' => substr($data['token'], 0, 8) . '...',
                    'organization_id' => $user->organization_id,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('Organization invitation accepted');

            return $this->success([
                'organization' => [
                    'uuid' => $user->organization->uuid,
                    'name' => $user->organization->name,
                ]
            ], 'Invitation accepted successfully');

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
