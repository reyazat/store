<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationInvitationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class CancelInvitationAction extends BaseAction
{
    public function __construct(
        protected OrganizationInvitationRepositoryInterface $invitationRepository,
        protected OrganizationService $organizationService
    ) {}

    public function execute(array $data = []): array
    {
        /** @var Organization $organization */
        $organization = $data['organization'];

        /** @var User $user */
        $user = $data['user'];

        $invitationId = $data['invitation_id'];

        try {
            // Check permission
            if (!$this->organizationService->hasPermission($organization, $user, 'users.invite')) {
                return $this->error('You do not have permission to cancel invitations', null, 403);
            }

            // Get invitation
            /** @var OrganizationInvitation $invitation */
            $invitation = $this->invitationRepository->find($invitationId);

            if (!$invitation || $invitation->organization_id !== $organization->id) {
                return $this->error('Invitation not found', null, 404);
            }

            // Check if invitation is pending
            if (!$this->invitationRepository->isPending($invitation)) {
                return $this->error('Invitation is not pending');
            }

            // Cancel invitation
            $this->invitationRepository->cancelInvitation($invitation);

            // Log invitation cancellation
            activity()
                ->performedOn($organization)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email
                ])
                ->log('Invitation cancelled');

            return $this->success(null, 'Invitation cancelled successfully');

        } catch (Exception $e) {
            return $this->error('Failed to cancel invitation: ' . $e->getMessage(), null, 500);
        }
    }
}
