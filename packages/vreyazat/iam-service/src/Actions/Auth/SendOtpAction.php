<?php

namespace IamApi\Actions\Auth;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OtpService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

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
        $identifier = $this->normalizeIdentifier($data['identifier']);
        $type = $data['type'];
        $channel = $data['channel'];

        // Rate limiting: read max attempts from config (falls back to 5)
        // config key: iam.otp.{channel}.rate_limit_per_hour (per-channel limit)
        $maxAttempts = (int) config('iam.otp.' . $channel . '.rate_limit_per_hour', 5);
        $windowSeconds = 3600; // 1 hour — matches "per_hour" naming in config
        $rateLimitKey = 'otp_send:' . $identifier . ':' . $type;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'تعداد درخواست‌های شما از حد مجاز فراتر رفته است. لطفاً بعداً دوباره تلاش کنید.',
                'code' => 429,
                'data' => [
                    'retry_after' => $seconds
                ]
            ];
        }

        // Hit rate limiter before processing to prevent enumeration
        RateLimiter::hit($rateLimitKey, $windowSeconds);

        try {
            // Find user based on identifier and type
            $user = $this->findUserByIdentifier($identifier, $type, $channel);

            if (!$user) {
                // Debug logging for production troubleshooting
                Log::warning('OTP user lookup failed', [
                    'identifier' => $identifier,
                    'type' => $type,
                    'channel' => $channel,
                    'request_phone_formats' => [
                        'original' => $data['identifier'] ?? null,
                        'normalized' => $identifier,
                        'with_zero' => '0' . ltrim($identifier, '+98'),
                        'without_country' => ltrim(ltrim($identifier, '+'), '98'),
                    ],
                    'existing_phones_sample' => DB::table('users')
                        ->whereNotNull('phone')
                        ->where('phone', 'like', '%' . substr($identifier, -8) . '%')
                        ->pluck('phone')
                        ->toArray(),
                ]);

                // Don't reveal if user exists or not for security (but rate limit is already hit)
                return [
                    'success' => false,
                    'message' => 'اطلاعات وارد شده نامعتبر است.',
                    'code' => 400
                ];
            }

            // Check if a user account is active (except for account activation)
            if ($type !== 'account_activation' && $user->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'حساب کاربری غیرفعال است. لطفاً با پشتیبانی تماس بگیرید.',
                    'code' => 403
                ];
            }

            // Send OTP
            $result = $this->otpService->send($user, $type, $channel);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'code' => ($result['service_error'] ?? false) ? 503 : 500
                ];
            }

            return [
                'success' => true,
                'message' => 'کد تأیید به ' . $this->maskIdentifier($identifier) . ' ارسال شد',
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
                'message' => 'ارسال کد تأیید ناموفق بود. لطفاً دوباره تلاش کنید.',
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

            // For phone verification or login verification, phone might not be verified yet
            // Login via phone is a form of verification itself, so we don't require phone_verified_at
            if ($type === 'phone_verification' || $type === 'login_verification') {
                return $user;
            }

            // For other types (like password_reset), require phone to be verified
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
     * Verify OTP code for a user
     * This is used internally by ForgotPasswordAction
     */
    public function verifyOtp(array $data): array
    {
        $identifier = $this->normalizeIdentifier($data['identifier']);
        $code = $data['code'];
        $type = $data['type'];
        $channel = $data['channel'] ?? 'email';

        // Find user
        $user = $this->findUserByIdentifier($identifier, $type, $channel);

        if (!$user) {
            return [
                'success' => false,
                'message' => __('auth.otp.user_not_found'),
                'data' => ['verified' => false]
            ];
        }

        // Use OtpService to verify the code (returns array with success/data)
        $result = $this->otpService->verify($user, $code, $type, $channel);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'OTP verified successfully',
                'data' => [
                    'verified' => true,
                    'identifier' => $this->maskIdentifier($identifier)
                ]
            ];
        }

        // Return the error with attempts remaining
        return [
            'success' => false,
            'message' => $result['message'] ?? __('auth.otp.invalid_or_expired'),
            'data' => [
                'verified' => false,
                'attempts_remaining' => $result['data']['attempts_remaining'] ?? 0
            ]
        ];
    }
}
