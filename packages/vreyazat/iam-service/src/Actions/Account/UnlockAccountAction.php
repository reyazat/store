<?php

namespace IamApi\Actions\Account;

use IamApi\Models\User;
use IamApi\Services\OtpService;
use IamApi\Services\TokenService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class UnlockAccountAction
{
    public function __construct(
        public OtpService $otpService,
        public TokenService $tokenService
    ) {}

    /**
     * Execute the unlock account action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];
        $unlockMethod = $data['unlock_method'];
        $verificationCode = $data['verification_code'] ?? null;
        $adminToken = $data['admin_token'] ?? null;

        // Rate limiting key
        $rateLimitKey = 'account_unlock:' . $user->id;

        // Check rate limiting (3 attempts per 15 minutes)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'Too many unlock attempts. Please try again later.',
                'code' => 429,
                'data' => [
                    'retry_after' => $seconds
                ]
            ];
        }

        try {
            // Check if an account is actually locked
            if (!$user->locked_until || $user->locked_until->isPast()) {
                return [
                    'success' => false,
                    'message' => 'Account is not currently locked.',
                    'code' => 400
                ];
            }

            // Handle different unlock methods
            $unlockResult = match ($unlockMethod) {
                'otp' => $this->unlockWithOtp($user, $verificationCode),
                'admin' => $this->unlockWithAdminToken($user, $adminToken),
                'time_based' => $this->unlockTimeBased($user),
                'security_question' => $this->unlockWithSecurityQuestion($user, $data['security_answer'] ?? null),
                default => [
                    'success' => false,
                    'message' => 'Invalid unlock method.',
                    'code' => 400
                ]
            };

            if (!$unlockResult['success']) {
                RateLimiter::hit($rateLimitKey, 900);
                return $unlockResult;
            }

            // Clear rate limit on successful unlock
            RateLimiter::clear($rateLimitKey);

            // Unlock the account
            $user->update([
                'locked_until' => null,
                'failed_login_attempts' => 0
            ]);

            // Log successful unlock
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'unlock_method' => $unlockMethod,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('Account unlocked successfully');

            return [
                'success' => true,
                'message' => 'Account unlocked successfully',
                'data' => [
                    'unlocked_at' => now()->toISOString(),
                    'unlock_method' => $unlockMethod,
                    'failed_login_attempts' => 0,
                    'can_attempt_login' => true
                ]
            ];

        } catch (Exception $e) {
            Log::error('Unlock account failed', [
                'user_id' => $user->id,
                'unlock_method' => $unlockMethod,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to unlock account. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Unlock an account using OTP verification
     */
    private function unlockWithOtp(User $user, ?string $verificationCode): array
    {
        if (!$verificationCode) {
            return [
                'success' => false,
                'message' => 'Verification code is required for OTP unlock method.',
                'code' => 400
            ];
        }

        // Verify OTP code
        $otpResult = $this->otpService->verify($user, $verificationCode, 'account_unlock');

        if (!$otpResult['success']) {
            return [
                'success' => false,
                'message' => 'Invalid verification code.',
                'code' => 400,
                'data' => [
                    'attempts_remaining' => $otpResult['data']['attempts_remaining'] ?? 0
                ]
            ];
        }

        return ['success' => true];
    }

    /**
     * Unlock an account using admin token
     */
    private function unlockWithAdminToken(User $user, ?string $adminToken): array
    {
        if (!$adminToken) {
            return [
                'success' => false,
                'message' => 'Admin token is required for admin unlock method.',
                'code' => 400
            ];
        }

        // Verify admin unlock token
        $tokenResult = $this->tokenService->verifyAdminUnlockToken($adminToken, $user->id);

        if (!$tokenResult['valid']) {
            return [
                'success' => false,
                'message' => $tokenResult['error'] ?? 'Invalid admin token.',
                'code' => 400
            ];
        }

        return ['success' => true];
    }

    /**
     * Unlock account based on time expiry
     */
    private function unlockTimeBased(User $user): array
    {
        // Check if enough time has passed for automatic unlocking
        if ($user->locked_until->isFuture()) {
            $remainingMinutes = $user->locked_until->diffInMinutes(now());

            return [
                'success' => false,
                'message' => "Account will be automatically unlocked in $remainingMinutes minutes.",
                'code' => 400,
                'data' => [
                    'unlock_in_minutes' => $remainingMinutes
                ]
            ];
        }

        return ['success' => true];
    }

    /**
     * Unlock an account using security question
     */
    private function unlockWithSecurityQuestion(User $user, ?string $securityAnswer): array
    {
        if (!$securityAnswer) {
            return [
                'success' => false,
                'message' => 'Security answer is required for security question unlock method.',
                'code' => 400
            ];
        }

        // This would typically verify against stored security questions/answers
        // For now, we'll implement a basic check

        // Get a user's security question answer (this would be stored securely)
        $storedAnswer = $user->security_answer ?? null;

        if (!$storedAnswer) {
            return [
                'success' => false,
                'message' => 'No security question configured for this account.',
                'code' => 400
            ];
        }

        // Verify answer (case-insensitive comparison)
        if (strtolower(trim($securityAnswer)) !== strtolower(trim($storedAnswer))) {
            return [
                'success' => false,
                'message' => 'Incorrect security answer.',
                'code' => 400
            ];
        }

        return ['success' => true];
    }
}
