<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\JwtToken;
use IamApi\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface JwtTokenRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find token by JTI.
     */
    public function findByJti(string $jti): ?JwtToken;

    /**
     * Find token by refresh token hash.
     */
    public function findByRefreshToken(string $refreshTokenHash): ?JwtToken;

    /**
     * Find active tokens for user.
     */
    public function findActiveTokensForUser(User $user): Collection;

    /**
     * Find all tokens for user (active and revoked).
     */
    public function findAllTokensForUser(User $user): Collection;

    /**
     * Find expired tokens.
     */
    public function findExpiredTokens(): Collection;

    /**
     * Revoke token by JTI.
     */
    public function revokeByJti(string $jti): bool;

    /**
     * Revoke all tokens for user.
     */
    public function revokeAllForUser(User $user): bool;

    /**
     * Revoke expired tokens.
     */
    public function revokeExpiredTokens(): int;

    /**
     * Clean up old revoked tokens.
     */
    public function cleanupOldTokens(int $daysOld = 30): int;

    /**
     * Update last used timestamp.
     */
    public function updateLastUsed(JwtToken $token): bool;

    /**
     * Find tokens by device name.
     */
    public function findByDevice(User $user, string $deviceName): Collection;

    /**
     * Find tokens by IP address.
     */
    public function findByIp(string $ip): Collection;
}
