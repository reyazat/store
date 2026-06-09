<?php

namespace IamApi\Actions\Organizations;

use IamApi\Actions\BaseAction;
use IamApi\Mail\OrganizationInvitationMail;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateInvitationAction extends BaseAction
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
            // Check permission
            if (!$this->organizationService->hasPermission($organization, $user, 'users.invite')) {
                return $this->error('You do not have permission to invite users', null, 403);
            }

            // Create invitation
            $invitation = $this->organizationService->inviteUser(
                $organization,
                $data['email'],
                $data['role'],
                $user
            );

            // Log invitation creation
            activity()
                ->performedOn($organization)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'invited_email' => $data['email'],
                    'role' => $data['role']
                ])
                ->log('User invited to organization');

            // Send invitation email
            try {
                Mail::to($data['email'])->send(new OrganizationInvitationMail($invitation));
            } catch (Exception $mailException) {
                // Email failure must not roll back the invitation — log and continue
                Log::error('Failed to send organization invitation email', [
                    'invitation_id' => $invitation->id,
                    'email'         => $data['email'],
                    'error'         => $mailException->getMessage(),
                ]);
            }

            return $this->success([
                'invitation' => [
                    'uuid' => $invitation->uuid,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'status' => $invitation->status,
                    'expires_at' => $invitation->expires_at,
                    'token' => $invitation->token,
                ]
            ], 'Invitation sent successfully');

        } catch (Exception $e) {
            return $this->error('Failed to create invitation: ' . $e->getMessage(), null, 500);
        }
    }
}
