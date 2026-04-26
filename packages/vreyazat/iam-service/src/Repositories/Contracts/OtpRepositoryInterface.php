<?php

namespace IamApi\Repositories\Contracts;

use IamApi\Models\Otp;
use IamApi\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface OtpRepositoryInterface extends CrudRepositoryInterface
{
    /**
     * Find active OTP for user and purpose.
     */
    public function findActiveOtp(User $user, string $purpose, string $channel = 'email'): ?Otp;

    /**
     * Find OTP by code hash.
     */
    public function findByCodeHash(string $codeHash): ?Otp;

    /**
     * Create OTP for user.
     */
    public function createForUser(User $user, string $purpose, string $channel, string $codeHash, int $expiryMinutes = 10): Model;

    /**
     * Mark OTP as used.
     */
    public function markAsUsed(Otp $otp): bool;

    /**
     * Increment attempts.
     */
    public function incrementAttempts(Otp $otp): bool;

    /**
     * Find expired OTPs.
     */
    public function findExpiredOtps(): Collection;

    /**
     * Clean up expired OTPs.
     */
    public function cleanupExpiredOtps(): int;

    /**
     * Find OTPs by purpose.
     */
    public function findByPurpose(string $purpose): Collection;

    /**
     * Find OTPs by channel.
     */
    public function findByChannel(string $channel): Collection;

    /**
     * Revoke all active OTPs for user and purpose.
     */
    public function revokeActiveOtps(User $user, string $purpose): int;

    /**
     * Check if OTP is valid and not expired.
     */
    public function isValidOtp(Otp $otp): bool;

    /**
     * Find recent OTPs for user (for rate limiting).
     */
    public function findRecentOtps(User $user, string $purpose, int $minutes = 60): Collection;
}
