<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\Organization;
use IamApi\Models\OrganizationDomain;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationDomainRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find domain by name.
     */
    public function findByDomain(string $domain): ?OrganizationDomain;

    /**
     * Find domains for organization.
     */
    public function findByOrganization(Organization $organization): Collection;

    /**
     * Find verified domains.
     */
    public function findVerifiedDomains(): Collection;

    /**
     * Find unverified domains.
     */
    public function findUnverifiedDomains(): Collection;

    /**
     * Create domain for organization.
     */
    public function createForOrganization(Organization $organization, string $domain): OrganizationDomain;

    /**
     * Verify domain.
     */
    public function verifyDomain(OrganizationDomain $domain): bool;

    /**
     * Check if the domain is verified.
     */
    public function isVerified(OrganizationDomain $domain): bool;

    /**
     * Check if domain exists for organization.
     */
    public function existsForOrganization(Organization $organization, string $domain): bool;

    /**
     * Delete domain from organization.
     */
    public function deleteFromOrganization(Organization $organization, string $domain): bool;

    /**
     * Find organization by domain.
     */
    public function findOrganizationByDomain(string $domain): ?Organization;

    /**
     * Check if email domain is allowed for organization.
     */
    public function isEmailDomainAllowed(Organization $organization, string $email): bool;
}
