<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use IamApi\Services\MfaService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VerifyRecoveryCodeAction
{
    public function __construct(
        public MfaService $mfaService
    ) {}

    /**
     * Execute the verify recovery code action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];
        $recoveryCode = $data['recovery_code'];

        // Rate limiting key
        $rateLimitKey = 'recovery_verify:' . $user->id;

        // Check rate limiting (3 attempts per 15 minutes)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'Too many recovery code attempts. Please try again later.',
                'code' => 429,
                'data' => [
                    'retry_after' => $seconds
                ]
            ];
        }

        try {
            // Check if MFA is enabled
            if (!$user->mfa_enabled) {
                return [
                    'success' => false,
                    'message' => 'MFA is not enabled for this account.',
                    'code' => 400
                ];
            }

            // Verify recovery code
            $result = $this->mfaService->verifyRecoveryCodeWithResponse($user, $recoveryCode);

            if (!$result['success']) {
                RateLimiter::hit($rateLimitKey, 900);

                // Log failed verification
                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'attempts_remaining' => 3 - RateLimiter::attempts($rateLimitKey)
                    ])
                    ->log('Failed MFA recovery code verification');

                return [
                    'success' => false,
                    'message' => 'Invalid or already used recovery code.',
                    'code' => 400,
                    'data' => [
                        'remaining_codes' => $user->recoveryCodes()->where('used_at', null)->count()
                    ]
                ];
            }

            // Clear rate limit on successful verification
            RateLimiter::clear($rateLimitKey);

            // Get remaining codes count
            $remainingCodes = $user->recoveryCodes()->where('used_at', null)->count();

            // Log successful recovery code usage
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'remaining_codes' => $remainingCodes
                ])
                ->log('MFA recovery code used successfully');

            return [
                'success' => true,
                'message' => 'Recovery code verified successfully',
                'data' => [
                    'verified' => true,
                    'remaining_codes' => $remainingCodes,
                    'used_at' => now()->toISOString(),
                    'warning' => 'This recovery code has been used and is no longer valid.'
                ]
            ];

        } catch (Exception $e) {
            Log::error('Verify recovery code failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to verify recovery code. Please try again later.',
                'code' => 500
            ];
        }
    }
}
