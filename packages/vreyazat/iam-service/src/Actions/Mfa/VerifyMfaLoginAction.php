<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\MfaService;
use IamApi\Services\TokenService;
use IamApi\Services\AuthService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VerifyMfaLoginAction
{
    public function __construct(
        public MfaService $mfaService,
        public TokenService $tokenService,
        public AuthService $authService,
        public UserRepositoryInterface $userRepository
    ) {}

    /**
     * Execute the verify MFA login action
     */
    public function execute(array $data): array
    {
        $loginToken = $data['login_token'];
        $type = $data['type']; // 'totp' or 'recovery_code'
        $code = $data['code'] ?? null;
        $recoveryCode = $data['recovery_code'] ?? null;

        try {
            // Verify login token
            $tokenResult = $this->tokenService->verifyToken($loginToken);

            if (!$tokenResult['valid']) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired login token.',
                    'code' => 400
                ];
            }

            // Check if token is for MFA verification
            $payload = $tokenResult['payload'];
            if (!isset($payload['mfa_required']) || !$payload['mfa_required']) {
                return [
                    'success' => false,
                    'message' => 'Token is not valid for MFA verification.',
                    'code' => 400
                ];
            }

            // Get user from token
            /** @var User $user */
            $user = $this->userRepository->find($payload['sub']);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found.',
                    'code' => 404
                ];
            }

            // Check if MFA is enabled for user
            if (!$user->mfa_enabled) {
                return [
                    'success' => false,
                    'message' => 'MFA is not enabled for this account.',
                    'code' => 400
                ];
            }

            // Rate limiting key
            $rateLimitKey = 'mfa_login_verify:' . $user->id;

            // Check rate limiting (5 attempts per 15 minutes)
            if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);

                return [
                    'success' => false,
                    'message' => 'Too many MFA verification attempts. Please try again later.',
                    'code' => 429,
                    'data' => [
                        'retry_after' => $seconds
                    ]
                ];
            }

            // Verify based on type
            if ($type === 'totp') {
                $result = $this->verifyTotp($user, $code);
            } elseif ($type === 'recovery_code') {
                $result = $this->verifyRecoveryCode($user, $recoveryCode);
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid verification type.',
                    'code' => 400
                ];
            }

            if (!$result['success']) {
                RateLimiter::hit($rateLimitKey, 900);

                // Log failed MFA verification
                activity()
                    ->performedOn($user)
                    ->withProperties([
                        'type' => $type,
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'attempts_remaining' => 5 - RateLimiter::attempts($rateLimitKey)
                    ])
                    ->log('Failed MFA login verification');

                return $result;
            }

            // Clear rate limit on successful verification
            RateLimiter::clear($rateLimitKey);

            // Blocklist the temporary login token
            $this->tokenService->blacklistToken($loginToken);

            // Generate final JWT tokens
            $tokens = $this->tokenService->issue($user);

            // Update login information
            $this->authService->touchLogin($user, request()->ip());

            // Log successful MFA login
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'method' => 'mfa_' . $type,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('User logged in via MFA');

            $responseData = [
                'user' => $user->only(['id','name','email','phone','status','email_verified_at','phone_verified_at','mfa_enabled']),
                'tokens' => $tokens
            ];

            // Add recovery code specific data
            if ($type === 'recovery_code') {
                $responseData['recovery_code_used'] = true;
                $responseData['remaining_codes'] = $user->recoveryCodes()->where('used_at', null)->count();
            }

            return [
                'success' => true,
                'message' => $type === 'totp' ? 'MFA verification successful' : 'Recovery code verification successful',
                'data' => $responseData
            ];

        } catch (Exception $e) {
            Log::error('Verify MFA login failed', [
                'error' => $e->getMessage(),
                'token_hash' => hash('sha256', $loginToken)
            ]);

            return [
                'success' => false,
                'message' => 'Failed to verify MFA. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Verify TOTP code
     */
    private function verifyTotp(User $user, ?string $code): array
    {
        if (!$code) {
            return [
                'success' => false,
                'message' => 'TOTP code is required.',
                'code' => 400
            ];
        }

        // Verify TOTP code
        $result = $this->mfaService->verifyTotp($user, $code);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Invalid TOTP code.',
                'code' => 400,
                'data' => [
                    'attempts_remaining' => 5 - RateLimiter::attempts('mfa_login_verify:' . $user->id)
                ]
            ];
        }


        return ['success' => true];
    }

    /**
     * Verify recovery code
     */
    private function verifyRecoveryCode(User $user, ?string $recoveryCode): array
    {
        if (!$recoveryCode) {
            return [
                'success' => false,
                'message' => 'Recovery code is required.',
                'code' => 400
            ];
        }

        // Verify recovery code
        $result = $this->mfaService->verifyRecoveryCodeWithResponse($user, $recoveryCode);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Invalid or already used recovery code.',
                'code' => 400,
                'data' => [
                    'remaining_codes' => $user->recoveryCodes()->where('used_at', null)->count()
                ]
            ];
        }

        return ['success' => true];
    }
}
