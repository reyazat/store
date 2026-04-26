<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\User;
use IamApi\Models\PasswordHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface PasswordHistoryRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find password history for user.
     */
    public function findByUser(User $user): Collection;

    /**
     * Find recent password history for user.
     */
    public function findRecentByUser(User $user, int $count = 5): Collection;

    /**
     * Create password history entry.
     */
    public function createForUser(User $user, string $passwordHash): Model;

    /**
     * Check if password exists in the user's history.
     */
    public function passwordExistsInHistory(User $user, string $passwordHash, int $historyCount = 5): bool;

    /**
     * Clean up old password history entries.
     */
    public function cleanupOldHistory(User $user, int $keepCount = 5): int;

    /**
     * Delete all password history for user.
     */
    public function deleteAllForUser(User $user): int;

    /**
     * Count password history entries for user.
     */
    public function countForUser(User $user): int;

    /**
     * Find the oldest password history entry for user.
     */
    public function findOldestForUser(User $user): ?PasswordHistory;

    /**
     * Find the newest password history entry for user.
     */
    public function findNewestForUser(User $user): ?PasswordHistory;
}
