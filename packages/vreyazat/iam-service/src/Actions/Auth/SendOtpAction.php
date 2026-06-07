<?php

namespace IamApi\Actions\Auth;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OtpService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SendOtpAction
{
    public function __construct(
        public OtpService $otpService,
        public UserRepositoryInterface $userRepository
    ) {}

    /**
     * Execute the send OTP action
     */
    public function execute(array $data): array
    {
        $identifier = $data['identifier'];
        $type = $data['type'];
        $channel = $data['channel'];

        // Rate limiting: max 5 send attempts per identifier+type per 15 minutes
        $rateLimitKey = 'otp_send:' . $identifier . ':' . $type;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'Too many OTP requests. Please try again later.',
                'code' => 429,
                'data' => [
                    'retry_after' => $seconds
                ]
            ];
        }

        // Hit rate limiter before processing to prevent enumeration
        RateLimiter::hit($rateLimitKey, 900); // 15 minutes window

        try {
            // Find user based on identifier and type
            $user = $this->findUserByIdentifier($identifier, $type, $channel);

            if (!$user) {
                // Don't reveal if user exists or not for security (but rate limit is already hit)
                return [
                    'success' => false,
                    'message' => 'Invalid identifier or user not found.',
                    'code' => 400
                ];
            }

            // Check if a user account is active (except for account activation)
            if ($type !== 'account_activation' && $user->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Account is not active. Please contact support.',
                    'code' => 403
                ];
            }

            // Send OTP
            $result = $this->otpService->send($user, $type, $channel);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'code' => 500
                ];
            }

            return [
                'success' => true,
                'message' => 'OTP sent successfully to ' . $this->maskIdentifier($identifier),
                'data' => [
                    'identifier' => $this->maskIdentifier($identifier),
                    'type' => $type,
                    'channel' => $channel,
                    'expires_at' => $result['data']['expires_at'],
                    'attempts_remaining' => $this->otpService->getRemainingAttempts($user, $type, $channel)
                ]
            ];

        } catch (Exception $e) {
            Log::error('Send OTP failed', [
                'identifier' => $identifier,
                'type' => $type,
                'channel' => $channel,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send OTP. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Find user by identifier based on OTP type and channel
     */
    private function findUserByIdentifier(string $identifier, string $type, string $channel): ?User
    {
        // For email channel or email-based types
        if ($channel === 'email' || filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userRepository->findByEmail($identifier);

            if (!$user) {
                return null;
            }

            // For email verification, user might not be verified yet
            if ($type === 'email_verification') {
                return $user;
            }

            // For other types, require email to be verified
            return $user->email_verified_at ? $user : null;
        }

        // For SMS channel or phone-based types
        if ($channel === 'sms' || preg_match('/^\+?[1-9]\d{1,14}$/', $identifier)) {
            $user = $this->userRepository->findByPhone($identifier);

            if (!$user) {
                return null;
            }

            // For phone verification, phone might not be verified yet
            if ($type === 'phone_verification') {
                return $user;
            }

            // For other types, require phone to be verified
            return $user->phone_verified_at ? $user : null;
        }

        return null;
    }

    /**
     * Mask identifier for security (show partial email/phone)
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
}
