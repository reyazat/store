<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\User;
use IamApi\Models\UserTotpSecret;
use Illuminate\Database\Eloquent\Model;

interface UserTotpSecretRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find TOTP secret for user.
     */
    public function findByUser(User $user): ?UserTotpSecret;

    /**
     * Create TOTP secret for user.
     */
    public function createForUser(User $user, string $secret): Model;

    /**
     * Enable TOTP for user.
     */
    public function enableForUser(User $user): bool;

    /**
     * Disable TOTP for user.
     */
    public function disableForUser(User $user): bool;

    /**
     * Check if user has TOTP enabled.
     */
    public function isEnabledForUser(User $user): bool;

    /**
     * Update TOTP secret for user.
     */
    public function updateSecretForUser(User $user, string $secret): bool;

    /**
     * Delete TOTP secret for user.
     */
    public function deleteForUser(User $user): bool;

    /**
     * Find all users with TOTP enabled.
     */
    public function findUsersWithTotpEnabled();

    /**
     * Find all users with TOTP disabled.
     */
    public function findUsersWithTotpDisabled();
}
