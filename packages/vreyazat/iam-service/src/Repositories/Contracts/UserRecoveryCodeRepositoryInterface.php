<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\User;
use IamApi\Models\UserRecoveryCode;
use Illuminate\Database\Eloquent\Collection;

interface UserRecoveryCodeRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find recovery codes for user.
     */
    public function findByUser(User $user): Collection;

    /**
     * Find unused recovery codes for user.
     */
    public function findUnusedByUser(User $user): Collection;

    /**
     * Find used recovery codes for user.
     */
    public function findUsedByUser(User $user): Collection;

    /**
     * Create recovery codes for user.
     */
    public function createCodesForUser(User $user, array $codeHashes);

    /**
     * Find recovery code by hash.
     */
    public function findByCodeHash(string $codeHash): ?UserRecoveryCode;

    /**
     * Mark recovery code as used.
     */
    public function markAsUsed(UserRecoveryCode $recoveryCode): bool;

    /**
     * Delete all recovery codes for user.
     */
    public function deleteAllForUser(User $user): int;

    /**
     * Count unused recovery codes for user.
     */
    public function countUnusedForUser(User $user): int;

    /**
     * Count used recovery codes for user.
     */
    public function countUsedForUser(User $user): int;

    /**
     * Check if user has unused recovery codes.
     */
    public function hasUnusedCodes(User $user): bool;

    /**
     * Regenerate recovery codes for user.
     */
    public function regenerateCodesForUser(User $user, array $codeHashes);
}
