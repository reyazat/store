<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationInvitationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class ResendInvitationAction extends BaseAction
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
                return $this->error('You do not have permission to resend invitations', null, 403);
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

            // Resend invitation (extends expiration and generates new token)
            $this->invitationRepository->resendInvitation($invitation);

            // Refresh the invitation to get updated data
            $invitation->refresh();

            // Log invitation resend
            activity()
                ->performedOn($organization)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email
                ])
                ->log('Invitation resent');

            // TODO: Resend invitation email
            // Mail::to($invitation->email)->send(new OrganizationInvitationMail($invitation));

            return $this->success([
                'invitation' => [
                    'uuid' => $invitation->uuid,
                    'email' => $invitation->email,
                    'expires_at' => $invitation->expires_at,
                ]
            ], 'Invitation resent successfully');

        } catch (Exception $e) {
            return $this->error('Failed to resend invitation: ' . $e->getMessage(), null, 500);
        }
    }
}
