<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\User;
use IamApi\Models\VerificationToken;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface VerificationTokenRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find token by token string.
     */
    public function findByToken(string $token): ?VerificationToken;

    /**
     * Find tokens for user.
     */
    public function findByUser(User $user): Collection;

    /**
     * Find tokens by type.
     */
    public function findByType(string $type): Collection;

    /**
     * Find active tokens for user and type.
     */
    public function findActiveByUserAndType(User $user, string $type): Collection;

    /**
     * Find expired tokens.
     */
    public function findExpiredTokens(): Collection;

    /**
     * Create a verification token for user.
     */
    public function createForUser(User $user, string $type, int $expiryMinutes = 60): Model;

    /**
     * Mark token as used.
     */
    public function markAsUsed(VerificationToken $token): bool;

    /**
     * Check if the token is expired.
     */
    public function isExpired(VerificationToken $token): bool;

    /**
     * Check if token is used.
     */
    public function isUsed(VerificationToken $token): bool;

    /**
     * Check if the token is valid (not expired and not used).
     */
    public function isValid(VerificationToken $token): bool;

    /**
     * Revoke all tokens for user and type.
     */
    public function revokeTokensForUserAndType(User $user, string $type): int;

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int;

    /**
     * Find recent tokens for user and type (for rate limiting).
     */
    public function findRecentTokens(User $user, string $type, int $minutes = 60): Collection;
}
