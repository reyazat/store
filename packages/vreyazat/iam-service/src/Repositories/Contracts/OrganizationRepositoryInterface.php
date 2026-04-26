<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find organization by name.
     */
    public function findByName(string $name): ?Organization;

    /**
     * Find organizations for user.
     */
    public function findByUser(User $user): Collection;

    /**
     * Find organization members.
     */
    public function findMembers(Organization $organization): Collection;

    /**
     * Find organization members by role.
     */
    public function findMembersByRole(Organization $organization, string $role): Collection;

    /**
     * Add member to organization.
     */
    public function addMember(Organization $organization, User $user, string $role = 'member'): bool;

    /**
     * Remove member from organization.
     */
    public function removeMember(Organization $organization, User $user): bool;

    /**
     * Update a member role in the organization.
     */
    public function updateMemberRole(Organization $organization, User $user, string $role): bool;

    /**
     * Check if user is member of organization.
     */
    public function isMember(Organization $organization, User $user): bool;

    /**
     * Check if a user has a specific role in the organization.
     */
    public function hasRole(Organization $organization, User $user, string $role): bool;

    /**
     * Get a user's role in organization.
     */
    public function getUserRole(Organization $organization, User $user): ?string;

    /**
     * Find organizations by domain.
     */
    public function findByDomain(string $domain): Collection;

    /**
     * Count members in organization.
     */
    public function countMembers(Organization $organization): int;

    /**
     * Find organization owners.
     */
    public function findOwners(Organization $organization): Collection;

    /**
     * Find organization admins.
     */
    public function findAdmins(Organization $organization): Collection;

    /**
     * Find organization users (role = 'user').
     */
    public function findUsers(Organization $organization): Collection;

    /**
     * Find organization members (role = 'member').
     */
    public function findRegularMembers(Organization $organization): Collection;

    /**
     * Check if organization has at least one owner.
     */
    public function hasOwner(Organization $organization): bool;

    /**
     * Count members by role.
     */
    public function countMembersByRole(Organization $organization, string $role): int;
}
