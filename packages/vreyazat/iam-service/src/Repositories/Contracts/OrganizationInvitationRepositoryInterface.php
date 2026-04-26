<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\Organization;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationInvitationRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find invitation by token.
     */
    public function findByToken(string $token): ?OrganizationInvitation;

    /**
     * Find invitation by email and organization.
     */
    public function findByEmailAndOrganization(string $email, Organization $organization): ?OrganizationInvitation;

    /**
     * Find invitations for organization.
     */
    public function findByOrganization(Organization $organization): Collection;

    /**
     * Find invitations by email.
     */
    public function findByEmail(string $email): Collection;

    /**
     * Find pending invitations.
     */
    public function findPendingInvitations(): Collection;

    /**
     * Find expired invitations.
     */
    public function findExpiredInvitations(): Collection;

    /**
     * Create invitation for organization.
     */
    public function createInvitation(Organization $organization, string $email, string $role, User $invitedBy): OrganizationInvitation;

    /**
     * Accept invitation.
     */
    public function acceptInvitation(OrganizationInvitation $invitation, User $user): bool;

    /**
     * Reject invitation.
     */
    public function rejectInvitation(OrganizationInvitation $invitation): bool;

    /**
     * Cancel invitation.
     */
    public function cancelInvitation(OrganizationInvitation $invitation): bool;

    /**
     * Check if the invitation is expired.
     */
    public function isExpired(OrganizationInvitation $invitation): bool;

    /**
     * Check if invitation is pending.
     */
    public function isPending(OrganizationInvitation $invitation): bool;

    /**
     * Clean up expired invitations.
     */
    public function cleanupExpiredInvitations(): int;

    /**
     * Resend invitation.
     */
    public function resendInvitation(OrganizationInvitation $invitation): bool;
}
