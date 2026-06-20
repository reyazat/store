<?php

namespace IamApi\Actions\Auth;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OtpService;
use IamApi\Services\TokenService;
use IamApi\Services\AuthService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VerifyOtpAction
{
    public function __construct(
        public OtpService $otpService,
        public TokenService $tokenService,
        public AuthService $authService,
        public UserRepositoryInterface $userRepository
    ) {}

    /**
     * Execute the verify OTP action
     */
    public function execute(array $data): array
    {
        $identifier = $data['identifier'];
        $code = $data['code'];
        $type = $data['type'];
        // Determine channel: if not provided, infer from identifier format
        $channel = $data['channel'] ?? (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms');

        // Rate limiting key for verification attempts
        $rateLimitKey = 'otp_verify:' . $identifier . ':' . $type;

        // Check rate limiting (10 attempts per 15 minutes)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please try again later.',
                'code' => 423,
                'data' => [
                    'locked_until' => now()->addSeconds($seconds)->toISOString(),
                    'retry_after' => $seconds
                ]
            ];
        }

        try {
            // Find user based on identifier
            $user = $this->userRepository->findByIdentifier($identifier);

            if (!$user) {
                RateLimiter::hit($rateLimitKey, 900);

                return [
                    'success' => false,
                    'message' => 'Invalid identifier or user not found.',
                    'code' => 400
                ];
            }

            // Verify OTP — pass channel so the correct OTP record is found
            $result = $this->otpService->verify($user, $code, $type, $channel);

            if (!$result['success']) {
                RateLimiter::hit($rateLimitKey, 900);

                // Log failed verification
                activity()
                    ->performedOn($user)
                    ->withProperties([
                        'type' => $type,
                        'ip' => request()->ip(),
                        'attempts_remaining' => $result['data']['attempts_remaining'] ?? 0
                    ])
                    ->log('Failed OTP verification');

                return $result;
            }

            // Clear rate limit on successful verification
            RateLimiter::clear($rateLimitKey);

            // Handle different OTP types
            return match ($type) {
                'login_verification' => $this->handleLoginVerification($user),
                'password_reset' => $this->handlePasswordReset($user),
                'email_verification' => $this->handleEmailVerification($user),
                'phone_verification' => $this->handlePhoneVerification($user),
                'account_activation' => $this->handleAccountActivation($user),
                'security_verification' => $this->handleSecurityVerification($user),
                default => [
                    'success' => false,
                    'message' => 'Invalid OTP type.',
                    'code' => 400
                ]
            };

        } catch (Exception $e) {
            Log::error('Verify OTP failed', [
                'identifier' => $identifier,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to verify OTP. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Handle login verification OTP
     */
    private function handleLoginVerification(User $user): array
    {
        // Check if a user account is active
        if ($user->status !== 'active') {
            return [
                'success' => false,
                'message' => 'Account is not active. Please contact support.',
                'code' => 403
            ];
        }

        // Check if an account is locked
        if ($user->locked_until && $user->locked_until->isFuture()) {
            return [
                'success' => false,
                'message' => 'Account is temporarily locked.',
                'code' => 423,
                'data' => [
                    'locked_until' => $user->locked_until->toISOString()
                ]
            ];
        }

        // Generate JWT tokens
        $tokens = $this->tokenService->issue($user);

        // Update login information
        $this->authService->touchLogin($user, request()->ip());

        // Log successful login
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'method' => 'otp_login',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ])
            ->log('User logged in via OTP');

        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'verified' => true,
                'type'     => 'login_verification',
                'user'     => $user->load('organization'),
                'tokens'   => $tokens,
            ]
        ];
    }

    /**
     * Handle password reset OTP
     */
    private function handlePasswordReset(User $user): array
    {
        // Generate password reset token (short-lived)
        $resetToken = $this->tokenService->issue($user, [
            'password_reset' => true,
            'exp' => now()->addMinutes(15)->timestamp
        ]);

        // Log password reset verification
        activity()
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ])
            ->log('Password reset OTP verified');

        return [
            'success' => true,
            'message' => 'OTP verified. You can now reset your password.',
            'data' => [
                'verified' => true,
                'type' => 'password_reset',
                'reset_token' => $resetToken['access_token'],
                'expires_in' => 900 // 15 minutes
            ]
        ];
    }

    /**
     * Handle email verification OTP
     */
    private function handleEmailVerification(User $user): array
    {
        // Mark email as verified
        $user->update([
            'email_verified_at' => now()
        ]);

        // Log email verification
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'email' => $user->email,
                'ip' => request()->ip()
            ])
            ->log('Email verified successfully');

        return [
            'success' => true,
            'message' => 'Email verified successfully',
            'data' => [
                'verified' => true,
                'type' => 'email_verification',
                'email_verified_at' => $user->email_verified_at->toISOString()
            ]
        ];
    }

    /**
     * Handle phone verification OTP
     */
    private function handlePhoneVerification(User $user): array
    {
        // Mark phone as verified
        $user->update([
            'phone_verified_at' => now()
        ]);

        // Log phone verification
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'phone' => $user->phone,
                'ip' => request()->ip()
            ])
            ->log('Phone verified successfully');

        return [
            'success' => true,
            'message' => 'Phone number verified successfully',
            'data' => [
                'verified' => true,
                'type' => 'phone_verification',
                'phone_verified_at' => $user->phone_verified_at->toISOString()
            ]
        ];
    }

    /**
     * Handle account activation OTP
     */
    private function handleAccountActivation(User $user): array
    {
        // Activate a user account
        $user->update([
            'status' => 'active',
            'email_verified_at' => $user->email_verified_at ?? now()
        ]);

        // Log account activation
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ])
            ->log('Account activated successfully');

        return [
            'success' => true,
            'message' => 'Account activated successfully',
            'data' => [
                'verified' => true,
                'type' => 'account_activation',
                'status' => $user->status,
                'activated_at' => now()->toISOString()
            ]
        ];
    }

    /**
     * Handle security verification OTP
     */
    private function handleSecurityVerification(User $user): array
    {
        // Generate security verification token (short-lived)
        $securityToken = $this->tokenService->issue($user, [
            'security_verification' => true,
            'exp' => now()->addMinutes(10)->timestamp
        ]);

        // Log security verification
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ])
            ->log('Security verification completed');

        return [
            'success' => true,
            'message' => 'Security verification completed',
            'data' => [
                'verified' => true,
                'type' => 'security_verification',
                'security_token' => $securityToken['access_token'],
                'expires_in' => 600 // 10 minutes
            ]
        ];
    }

}
