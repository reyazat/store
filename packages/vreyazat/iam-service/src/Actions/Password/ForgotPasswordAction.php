<?php

namespace IamApi\Actions\Password;

use IamApi\Actions\BaseAction;
use IamApi\Actions\Auth\SendOtpAction;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OtpRepositoryInterface;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ForgotPasswordAction extends BaseAction
{
    public function __construct(
        public SendOtpAction $sendOtpAction,
        public UserRepositoryInterface $userRepository,
        public OtpRepositoryInterface $otpRepository
    ) {}

    /**
     * Execute the forgot password action
     * Sends OTP to both email and phone simultaneously
     * Does not error if phone is missing (since email is always sent)
     * Always returns success to avoid user enumeration attacks
     */
    public function execute(array $data = []): array
    {
        // Normalize identifier first for consistent rate limiting key
        $identifier = $this->normalizeIdentifier($data['identifier'] ?? '');

        // Rate limiting: max 3 requests per identifier per 15 minutes
        $rateLimitKey = 'forgot_password:' . $identifier;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return $this->error('Too many password reset requests. Please try again later.', [
                'retry_after' => $seconds
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 900);

        try {
            // Find user by identifier
            $user = $this->userRepository->findByIdentifier($identifier);

            if (!$user) {
                // Security: don't reveal if user exists or not
                return $this->success([
                    'identifier' => $this->maskIdentifier($identifier),
                    'channels'   => [],
                    'expires_at' => null,
                ], 'Password reset request processed');
            }

            $emailSent = false;
            $smsSent = false;
            $expiresAt = null;
            $attemptsRemaining = null;

            // Always send to email if user has email
            if ($user->email) {
                $emailResult = $this->sendOtpAction->execute([
                    'identifier' => $user->email,
                    'type'       => 'password_reset',
                    'channel'    => 'email',
                ]);

                if ($emailResult['success']) {
                    $emailSent = true;
                    $expiresAt = $emailResult['data']['expires_at'] ?? null;
                    $attemptsRemaining = $emailResult['data']['attempts_remaining'] ?? null;
                }
            }

            // Also send to phone if user has phone (don't error if missing)
            if ($user->phone) {
                $smsResult = $this->sendOtpAction->execute([
                    'identifier' => $user->phone,
                    'type'       => 'password_reset',
                    'channel'    => 'sms',
                ]);

                if ($smsResult['success']) {
                    $smsSent = true;
                    if (!$expiresAt) {
                        $expiresAt = $smsResult['data']['expires_at'] ?? null;
                    }
                    if (!$attemptsRemaining) {
                        $attemptsRemaining = $smsResult['data']['attempts_remaining'] ?? null;
                    }
                }
            }

            $channels = array_filter(['email' => $emailSent, 'sms' => $smsSent]);
            $channels = array_keys($channels);

            // Security: always respond with success regardless of whether OTP was sent
            // This prevents enumeration attacks (even if user has no email this returns success)
            if ($emailSent || $smsSent) {
                activity()
                    ->performedOn($user)
                    ->withProperties([
                        'ip'                 => request()->ip(),
                        'channels'           => $channels,
                        'user_agent'         => request()->userAgent(),
                        'identifier_masked'  => $this->maskIdentifier($identifier),
                    ])
                    ->log('Password reset OTP sent');
            }

            return $this->success([
                'identifier'        => $this->maskIdentifier($identifier),
                'channels'          => $channels,
                'expires_at'        => $expiresAt,
                'attempts_remaining' => $attemptsRemaining,
            ], 'Password reset request processed');

        } catch (Exception $e) {
            Log::error('Forgot password failed', [
                'identifier' => $data['identifier'] ?? 'unknown',
                'error'      => $e->getMessage(),
            ]);

            // Still return success to avoid user enumeration
            return $this->success([
                'identifier' => $this->maskIdentifier($identifier),
                'channels'   => [],
                'expires_at' => null,
            ], 'Password reset request processed');
        }
    }

    /**
     * Mask identifier for security
     */
    private function maskIdentifier(string $identifier): string
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $identifier);
            $username = $parts[0];
            $domain = $parts[1];

            if (strlen($username) <= 2) {
                return str_repeat('*', strlen($username)) . '@' . $domain;
            }

            return substr($username, 0, 2) . str_repeat('*', strlen($username) - 2) . '@' . $domain;
        }

        // Phone number masking
        if (strlen($identifier) <= 4) {
            return str_repeat('*', strlen($identifier));
        }

        return substr($identifier, 0, 3) . str_repeat('*', strlen($identifier) - 6) . substr($identifier, -3);
    }

    /**
     * Verify OTP code for password reset
     * Uses the OtpService to verify the code
     */
    public function verifyOtp(array $data): array
    {
        try {
            // Normalize identifier to handle different phone formats
            $identifier = $this->normalizeIdentifier($data['identifier']);
            $code = $data['code'];

            // Find user by identifier
            $user = $this->userRepository->findByIdentifier($identifier);

            if (!$user) {
                return $this->error('Invalid identifier', null, 400);
            }

            // Try to verify against email first, then phone
            $verified = false;
            $attemptsRemaining = null;

            // Check email OTP
            if ($user->email) {
                $emailVerify = $this->sendOtpAction->verifyOtp([
                    'identifier' => $user->email,
                    'code' => $code,
                    'type' => 'password_reset',
                    'channel' => 'email'
                ]);

                if ($emailVerify['success'] && ($emailVerify['data']['verified'] ?? false)) {
                    $verified = true;
                } elseif (isset($emailVerify['data']['attempts_remaining'])) {
                    $attemptsRemaining = $emailVerify['data']['attempts_remaining'];
                }
            }

            // Check phone OTP if email didn't verify
            if (!$verified && $user->phone) {
                $phoneVerify = $this->sendOtpAction->verifyOtp([
                    'identifier' => $user->phone,
                    'code' => $code,
                    'type' => 'password_reset',
                    'channel' => 'sms'
                ]);

                if ($phoneVerify['success'] && ($phoneVerify['data']['verified'] ?? false)) {
                    $verified = true;
                } elseif (isset($phoneVerify['data']['attempts_remaining'])) {
                    $attemptsRemaining = $phoneVerify['data']['attempts_remaining'];
                }
            }

            if ($verified) {
                // Security: Revoke all other active OTPs for this user
                // This prevents the race condition where the other channel's OTP is still valid
                $this->revokeAllUserOtps($user, 'password_reset');

                // Generate a unique reset token (UUID) for secure password reset
                // This prevents cache key collision and replay attacks
                $resetToken = bin2hex(random_bytes(32));
                $cacheKey = 'password_reset_token:' . $resetToken;

                // Store user_id and expiry in cache (10 minutes)
                cache()->put($cacheKey, [
                    'user_id' => $user->id,
                    'identifier' => $identifier,
                    'created_at' => now()->toISOString()
                ], now()->addMinutes(10));

                // Log password reset OTP verified
                activity()
                    ->performedOn($user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'identifier_masked' => $this->maskIdentifier($identifier)
                    ])
                    ->log('Password reset OTP verified');

                return $this->success([
                    'verified' => true,
                    'identifier' => $this->maskIdentifier($identifier),
                    'reset_token' => $resetToken,
                    'expires_in' => 600 // 10 minutes in seconds
                ], 'OTP verified successfully');
            }

            // Not verified - return appropriate error
            return $this->error(
                'Invalid or expired OTP code',
                ['attempts_remaining' => $attemptsRemaining],
                400
            );

        } catch (Exception $e) {
            Log::error('Password reset OTP verification failed', [
                'identifier' => $data['identifier'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->error('Failed to verify OTP code', null, 500);
        }
    }

    /**
     * Reset password with verified OTP
     * Requires the OTP to have been verified first (using reset_token)
     */
    public function resetWithOtp(array $data): array
    {
        try {
            // Normalize identifier to handle different phone formats
            $identifier = $this->normalizeIdentifier($data['identifier']);
            $resetToken = $data['reset_token'];
            $newPassword = $data['new_password'];

            // Find user
            $user = $this->userRepository->findByIdentifier($identifier);

            if (!$user) {
                return $this->error('Invalid identifier', null, 400);
            }

            // Verify the reset token from cache (prevents replay attacks)
            $cacheKey = 'password_reset_token:' . $resetToken;
            $cachedData = cache()->get($cacheKey);

            if (!$cachedData) {
                return $this->error('Reset token not found or expired. Please verify the OTP code again.', null, 400);
            }

            // Verify that the cached user_id matches the found user
            if ($cachedData['user_id'] !== $user->id) {
                return $this->error('Invalid reset token for this user.', null, 400);
            }

            // Clear the verification cache (one-time use)
            cache()->forget($cacheKey);

            // Revoke all active OTPs for this user (security: invalidate any remaining codes)
            $this->revokeAllUserOtps($user, 'password_reset');

            // Update password
            $user->password = bcrypt($newPassword);
            $user->password_changed_at = now();
            $user->must_change_password = false;
            $user->save();

            // Log password reset completed
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'password_changed_at' => $user->password_changed_at->toDateTimeString()
                ])
                ->log('Password reset completed');

            return $this->success([
                'password_changed_at' => $user->password_changed_at->toDateTimeString(),
                'password_expires_at' => $user->password_expires_at?->toDateTimeString(),
                'must_change_password' => false
            ], 'Password reset successfully');

        } catch (Exception $e) {
            Log::error('Password reset with OTP failed', [
                'identifier' => $data['identifier'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->error('Failed to reset password', null, 500);
        }
    }

    /**
     * Normalize identifier - especially for Iranian phone numbers
     * Converts 09XXXXXXXXX to +989XXXXXXXXX format
     */
    private function normalizeIdentifier(string $identifier): string
    {
        // If it's an email, just lowercase it
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return strtolower($identifier);
        }

        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $identifier);

        // Iranian phone number normalization
        // Handle formats: 09XXXXXXXXX, 9XXXXXXXXX, +989XXXXXXXXX
        if (preg_match('/^0?9\d{9}$/', $phone)) {
            // Remove leading 0 if present and add +98
            $phone = preg_replace('/^0?9/', '+989', $phone);
        }

        return $phone;
    }

    /**
     * Revoke all active OTPs for a user across all channels (security measure)
     * Prevents replay attacks where an OTP on one channel is still valid after
     * verification on the other channel.
     */
    private function revokeAllUserOtps(User $user, string $purpose): void
    {
        try {
            // revokeActiveOtps revokes all channels (no channel filter in query)
            $this->otpRepository->revokeActiveOtps($user, $purpose);
        } catch (Exception $e) {
            Log::warning('Failed to revoke user OTPs', [
                'user_id' => $user->id,
                'purpose' => $purpose,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
