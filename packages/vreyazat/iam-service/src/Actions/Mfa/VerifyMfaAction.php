<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use IamApi\Services\MfaService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VerifyMfaAction
{
    public function __construct(
        public MfaService $mfaService
    ) {}

    /**
     * Execute the verify MFA action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];
        $code = $data['code'];

        // Rate limiting key
        $rateLimitKey = 'mfa_verify:' . $user->id;

        // Check rate limiting (5 attempts per 15 minutes)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'Too many verification attempts. Please try again later.',
                'code' => 429,
                'data' => [
                    'retry_after' => $seconds
                ]
            ];
        }

        try {
            // Check if user has TOTP secret configured
            if (!$user->totpSecret) {
                return [
                    'success' => false,
                    'message' => 'MFA is not configured for this account.',
                    'code' => 400
                ];
            }
            // Verify TOTP code
            $result = $this->mfaService->verifyTotp($user, $code);

            if (!$result['success']) {
                RateLimiter::hit($rateLimitKey, 900);

                // Log failed verification
                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'attempts_remaining' => 5 - RateLimiter::attempts($rateLimitKey)
                    ])
                    ->log('Failed MFA verification during setup');

                return [
                    'success' => false,
                    'message' => 'Invalid TOTP code.',
                    'code' => 400,
                    'data' => [
                        'attempts_remaining' => 5 - RateLimiter::attempts($rateLimitKey)
                    ]
                ];
            }

            // Clear rate limit on successful verification
            RateLimiter::clear($rateLimitKey);

            // Complete MFA setup using service
            $setupResult = $this->mfaService->completeMfaSetup($user, $code);

            if (!$setupResult['success']) {
                return $setupResult;
            }

            // Log successful MFA enablement
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('MFA enabled successfully');

            return [
                'success' => true,
                'message' => 'MFA enabled successfully',
                'data' => [
                    'mfa_enabled' => true,
                    'enabled_at' => now()->toISOString(),
                    'recovery_codes_count' => $user->recoveryCodes()->where('used_at', null)->count()
                ]
            ];

        } catch (Exception $e) {
            Log::error('Verify MFA failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to verify MFA. Please try again later.',
                'code' => 500
            ];
        }
    }
}
