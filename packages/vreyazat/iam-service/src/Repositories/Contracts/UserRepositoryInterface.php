<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by phone.
     */
    public function findByPhone(string $phone): ?User;

    /**
     * Find user by identifier (email or phone).
     */
    public function findByIdentifier(string $identifier): ?User;

    /**
     * Find active users.
     */
    public function findActiveUsers(): Collection;

    /**
     * Find locked users.
     */
    public function findLockedUsers(): Collection;

    /**
     * Find users with expired passwords.
     */
    public function findUsersWithExpiredPasswords(): Collection;

    /**
     * Find users who must change password.
     */
    public function findUsersMustChangePassword(): Collection;

    /**
     * Lock user account until a specific time.
     */
    public function lockUser(User $user, Carbon $until): bool;

    /**
     * Unlock a user account.
     */
    public function unlockUser(User $user): bool;

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedAttempts(User $user): bool;

    /**
     * Reset failed login attempts.
     */
    public function resetFailedAttempts(User $user): bool;

    /**
     * Update last login information.
     */
    public function updateLastLogin(User $user, string $ip): bool;

    /**
     * Force password change.
     */
    public function forcePasswordChange(User $user): bool;

    /**
     * Update password with history tracking.
     */
    public function updatePasswordWithHistory(User $user, string $password): bool;

    /**
     * Check if the password exists in history.
     */
    public function passwordExistsInHistory(User $user, string $password, int $historyCount = 5): bool;

    /**
     * Find users by organization.
     */
    public function findByOrganization(int $organizationId): Collection;

    /**
     * Find users by role in organization.
     */
    public function findByOrganizationRole(int $organizationId, string $role): Collection;

    /**
     * Find all Super Admins.
     */
    public function findSuperAdmins(): Collection;

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(User $user): bool;

    /**
     * Find all owners across all organizations.
     */
    public function findAllOwners(): Collection;
}
