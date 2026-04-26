<?php

namespace IamApi\Services;

use IamApi\Models\Organization;
use IamApi\Models\OrganizationDomain;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Repositories\Contracts\OrganizationInvitationRepositoryInterface;
use IamApi\Repositories\Contracts\OrganizationDomainRepositoryInterface;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Exception;
use InvalidArgumentException;

class OrganizationService
{
    protected OrganizationRepositoryInterface $organizationRepository;
    protected OrganizationInvitationRepositoryInterface $invitationRepository;
    protected OrganizationDomainRepositoryInterface $domainRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        OrganizationRepositoryInterface $organizationRepository,
        OrganizationInvitationRepositoryInterface $invitationRepository,
        OrganizationDomainRepositoryInterface $domainRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->organizationRepository = $organizationRepository;
        $this->invitationRepository = $invitationRepository;
        $this->domainRepository = $domainRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Create new organization
     */
    public function createOrganization(array $data, User $owner): Organization
    {
        /** @var Organization $organization */
        $organization = $this->organizationRepository->create($data);

        // Add owner to organization
        $this->organizationRepository->addMember($organization, $owner, 'owner');

        return $organization;
    }

    /**
     * Add member to organization
     */
    public function addMember(Organization $organization, User $user, string $role = 'member'): bool
    {
        if (!in_array($role, ['owner', 'admin', 'user', 'member'])) {
            throw new InvalidArgumentException('Invalid role. Must be one of: owner, admin, user, member');
        }

        return $this->organizationRepository->addMember($organization, $user, $role);
    }

    /**
     * Remove member from organization
     * @throws Exception
     */
    public function removeMember(Organization $organization, User $user): bool
    {
        // Prevent removing the last owner
        if ($this->organizationRepository->hasRole($organization, $user, 'owner')) {
            $owners = $this->organizationRepository->findOwners($organization);
            if ($owners->count() <= 1) {
                throw new Exception('Cannot remove the last owner from organization');
            }
        }

        return $this->organizationRepository->removeMember($organization, $user);
    }

    /**
     * Update member role
     * @throws Exception
     */
    public function updateMemberRole(Organization $organization, User $user, string $role): bool
    {
        if (!in_array($role, ['owner', 'admin', 'user', 'member'])) {
            throw new InvalidArgumentException('Invalid role. Must be one of: owner, admin, user, member');
        }

        // Prevent demoting the last owner
        if ($this->organizationRepository->hasRole($organization, $user, 'owner') && $role !== 'owner') {
            $owners = $this->organizationRepository->findOwners($organization);
            if ($owners->count() <= 1) {
                throw new Exception('Cannot demote the last owner');
            }
        }

        return $this->organizationRepository->updateMemberRole($organization, $user, $role);
    }

    /**
     * Invite user to organization
     * @throws Exception
     */
    public function inviteUser(Organization $organization, string $email, string $role, User $invitedBy): OrganizationInvitation
    {
        if (!in_array($role, ['admin', 'user', 'member'])) {
            throw new InvalidArgumentException('Invalid role for invitation. Must be one of: admin, user, member');
        }

        // Check if user is already a member or assigned to another organization
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            // In single-organization model, check if user is already assigned
            if ($existingUser->organization_id) {
                if ($existingUser->organization_id === $organization->id) {
                    throw new Exception('User is already a member of this organization');
                } else {
                    throw new Exception('User is already assigned to another organization');
                }
            }
        }

        // Check for existing pending invitation
        $existingInvitation = $this->invitationRepository->findByEmailAndOrganization($email, $organization);
        if ($existingInvitation && $this->invitationRepository->isPending($existingInvitation)) {
            throw new Exception('Pending invitation already exists for this email');
        }

        return $this->invitationRepository->createInvitation($organization, $email, $role, $invitedBy);
    }

    /**
     * Accept organization invitation
     * @throws Exception
     */
    public function acceptInvitation(string $token, User $user): bool
    {
        $invitation = $this->invitationRepository->findByToken($token);

        if (!$invitation || !$this->invitationRepository->isPending($invitation)) {
            throw new Exception('Invalid or expired invitation');
        }

        if ($invitation->email !== $user->email) {
            throw new Exception('Invitation email does not match user email');
        }

        return $this->invitationRepository->acceptInvitation($invitation, $user);
    }

    /**
     * Reject organization invitation
     * @throws Exception
     */
    public function rejectInvitation(string $token): bool
    {
        $invitation = $this->invitationRepository->findByToken($token);

        if (!$invitation || !$this->invitationRepository->isPending($invitation)) {
            throw new Exception('Invalid or expired invitation');
        }

        return $this->invitationRepository->rejectInvitation($invitation);
    }

    /**
     * Add domain to organization
     * @throws Exception
     */
    public function addDomain(Organization $organization, string $domain): OrganizationDomain
    {
        if ($this->domainRepository->existsForOrganization($organization, $domain)) {
            throw new Exception('Domain already exists for this organization');
        }

        return $this->domainRepository->createForOrganization($organization, $domain);
    }

    /**
     * Verify organization domain
     * @throws Exception
     */
    public function verifyDomain(Organization $organization, string $domain): bool
    {
        $organizationDomain = $this->domainRepository->findByDomain($domain);

        if (!$organizationDomain || $organizationDomain->organization_id !== $organization->id) {
            throw new Exception('Domain not found for this organization');
        }

        // TODO: Implement actual domain verification logic
        // This could involve DNS TXT record verification, etc.

        return $this->domainRepository->verifyDomain($organizationDomain);
    }

    /**
     * Remove domain from organization
     */
    public function removeDomain(Organization $organization, string $domain): bool
    {
        return $this->domainRepository->deleteFromOrganization($organization, $domain);
    }

    /**
     * Check if user can access organization
     */
    public function canUserAccess(Organization $organization, User $user): bool
    {
        return $this->organizationRepository->isMember($organization, $user);
    }

    /**
     * Get user's role in organization
     */
    public function getUserRole(Organization $organization, User $user): ?string
    {
        return $this->organizationRepository->getUserRole($organization, $user);
    }

    /**
     * Check if user has specific permission in organization.
     *
     * Note: This is a simple permission check based on organization role (Layer 1).
     * For more granular permissions, use Spatie Permission (Layer 2) via $user->can().
     */
    public function hasPermission(Organization $organization, User $user, string $permission): bool
    {
        // Super Admin has all permissions
        if ($user->isSuperAdmin()) {
            return true;
        }

        $role = $this->getUserRole($organization, $user);

        if (!$role) {
            return false;
        }

        // Define role permissions (Layer 1 - Organization Role)
        $permissions = [
            'owner' => ['*'], // All permissions
            'admin' => [
                'users.manage',
                'users.invite',
                'domains.manage',
                'settings.manage'
            ],
            'user' => [
                'users.view',
                'domains.view'
            ],
            'member' => [
                'users.view'
            ]
        ];

        $rolePermissions = $permissions[$role] ?? [];

        return in_array('*', $rolePermissions) || in_array($permission, $rolePermissions);
    }

    /**
     * Get organization statistics
     */
    public function getStatistics(Organization $organization): array
    {
        return [
            'total_members' => $this->organizationRepository->countMembers($organization),
            'owners_count' => $this->organizationRepository->countMembersByRole($organization, 'owner'),
            'admins_count' => $this->organizationRepository->countMembersByRole($organization, 'admin'),
            'users_count' => $this->organizationRepository->countMembersByRole($organization, 'user'),
            'members_count' => $this->organizationRepository->countMembersByRole($organization, 'member'),
            'domains_count' => $this->domainRepository->findByOrganization($organization)->count(),
            'verified_domains_count' => $this->domainRepository->findByOrganization($organization)
                ->filter(fn($domain) => $this->domainRepository->isVerified($domain))->count(),
            'pending_invitations' => $this->invitationRepository->findByOrganization($organization)
                ->filter(fn($inv) => $this->invitationRepository->isPending($inv))->count()
        ];
    }

    /**
     * Clean up expired invitations
     */
    public function cleanupExpiredInvitations(): int
    {
        return $this->invitationRepository->cleanupExpiredInvitations();
    }
}
