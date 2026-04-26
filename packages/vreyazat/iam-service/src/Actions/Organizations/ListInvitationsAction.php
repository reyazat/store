<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationInvitationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Exception;

class ListInvitationsAction extends BaseAction
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

        try {
            // Check permission
            if (!$this->organizationService->hasPermission($organization, $user, 'users.invite')) {
                return $this->error('You do not have permission to view invitations', null, 403);
            }

            // Get all invitations for the organization
            $invitations = $this->invitationRepository->findByOrganization($organization);

            $invitationsData = $invitations->map(function ($invitation) {
                return [
                    'uuid' => $invitation->uuid,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'status' => $invitation->status,
                    'invited_by' => [
                        'uuid' => $invitation->invitedBy->uuid,
                        'name' => $invitation->invitedBy->name,
                        'email' => $invitation->invitedBy->email,
                    ],
                    'created_at' => $invitation->created_at,
                    'expires_at' => $invitation->expires_at,
                ];
            });

            return $this->success([
                'invitations' => $invitationsData,
                'total' => $invitationsData->count(),
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to fetch invitations: ' . $e->getMessage(), null, 500);
        }
    }
}
