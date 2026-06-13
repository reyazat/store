<?php

namespace IamApi\Services;

use IamApi\Mail\OtpMail;
use IamApi\Models\User;
use IamApi\Models\Otp;
use IamApi\Repositories\Contracts\OtpRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kavenegar\Laravel\Facade as Kavenegar;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;
use Random\RandomException;

class OtpService
{
    protected OtpRepositoryInterface $otpRepository;

    public function __construct(OtpRepositoryInterface $otpRepository)
    {
        $this->otpRepository = $otpRepository;
    }

    /**
     * Generate and send OTP for user
     * @throws RandomException
     */
    public function generateOtp(User $user, string $purpose, string $channel = 'email', int $expiryMinutes = 10): array
    {
        // Revoke any existing active OTPs
        $this->otpRepository->revokeActiveOtps($user, $purpose);

        // Generate OTP code
        $code = $this->generateCode();
        $codeHash = Hash::make($code);

        // Create OTP record
        /** @var Otp $otp */
        $otp = $this->otpRepository->createForUser($user, $purpose, $channel, $codeHash, $expiryMinutes);

        return [
            'code' => $code,
            'otp' => $otp,
            'expires_at' => $otp->expires_at
        ];
    }

    /**
     * Verify OTP code with detailed response
     */
    public function verify(User $user, string $code, string $purpose, string $channel = 'email'): array
    {
        $otp = $this->otpRepository->findActiveOtp($user, $purpose, $channel);

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'No active OTP found for this user.',
                'data' => [
                    'attempts_remaining' => 0
                ]
            ];
        }

        if (!$this->otpRepository->isValidOtp($otp)) {
            return [
                'success' => false,
                'message' => 'OTP has expired or is no longer valid.',
                'data' => [
                    'attempts_remaining' => 0
                ]
            ];
        }

        // Check if max attempts exceeded
        if ($otp->attempts >= $otp->max_attempts) {
            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded.',
                'data' => [
                    'attempts_remaining' => 0
                ]
            ];
        }

        // Increment attempts — use attempts+1 because the model is not refreshed after update
        $this->otpRepository->incrementAttempts($otp);
        $attemptsAfterIncrement = $otp->attempts + 1;
        $remainingAttempts = max(0, $otp->max_attempts - $attemptsAfterIncrement);

        // Check if code matches
        if (!Hash::check($code, $otp->code_hash)) {
            return [
                'success' => false,
                'message' => 'Invalid OTP code.',
                'data' => [
                    'attempts_remaining' => $remainingAttempts
                ]
            ];
        }

        // Mark as used
        $this->otpRepository->markAsUsed($otp);

        return [
            'success' => true,
            'message' => 'OTP verified successfully.',
            'data' => [
                'otp_id' => $otp->id,
                'verified_at' => now()->toISOString(),
                'purpose' => $purpose,
                'channel' => $channel
            ]
        ];
    }

    /**
     * Get remaining attempts for OTP
     */
    public function getRemainingAttempts(User $user, string $purpose, string $channel = 'email'): int
    {
        $otp = $this->otpRepository->findActiveOtp($user, $purpose, $channel);

        if (!$otp) {
            return 0;
        }

        return max(0, $otp->max_attempts - $otp->attempts);
    }

    /**
     * Clean up expired OTPs
     */
    public function cleanupExpiredOtps(): int
    {
        return $this->otpRepository->cleanupExpiredOtps();
    }

    /**
     * Generate OTP code
     * @throws RandomException
     */
    protected function generateCode(int $length = 6): string
    {
        return str_pad((string) random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via specified channel
     */
    public function sendOtp(User $user, string $code, string $channel, string $purpose): bool
    {
        return match ($channel) {
            'email' => $this->sendOtpEmail($user, $code, $purpose),
            'sms' => $this->sendOtpSms($user, $code, $purpose),
            default => false,
        };
    }

    /**
     * Send OTP via email using OtpMail mailable
     */
    protected function sendOtpEmail(User $user, string $code, string $purpose): bool
    {
        try {
            if (!$user->email) {
                Log::warning('Cannot send email OTP: User has no email address', ['user_id' => $user->id]);
                return false;
            }

            $expiryMinutes = (int) config('iam.otp.email.expiry_minutes', 10);

            Mail::to($user->email)->send(new OtpMail($code, $purpose, $expiryMinutes));

            Log::info('Email OTP sent successfully', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'purpose' => $purpose,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send email OTP', [
                'user_id' => $user->id,
                'email'   => $user->email ?? null,
                'purpose' => $purpose,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send OTP via SMS using Kavenegar VerifyLookup (template-based)
     *
     * از متد VerifyLookup کاوه‌نگار با الگوی store-otp استفاده می‌کند.
     * الگو: "کد تایید سامانه ی فروشگاه شرکت تجارت: %token"
     * مستندات: https://kavenegar.com/rest.html#lookup-verify
     */
    protected function sendOtpSms(User $user, string $code, string $purpose): bool
    {
        try {
            if (!$user->phone) {
                Log::warning('Cannot send SMS OTP: User has no phone number', ['user_id' => $user->id]);
                return false;
            }

            $template = config('kavenegar.otp_template', 'store-otp');
            $receptor = $user->phone;

            // VerifyLookup(receptor, token, token2, token3, template, type)
            // token  = کد OTP (جایگزین %token در الگو)
            // token2 = null (در الگوی ما نیاز نیست)
            // token3 = null (در الگوی ما نیاز نیست)
            $result = Kavenegar::VerifyLookup($receptor, $code, null, null, $template);

            if ($result && count($result) > 0) {
                $smsResult = $result[0];

                Log::info('SMS OTP sent successfully via Kavenegar Lookup', [
                    'user_id'    => $user->id,
                    'phone'      => $receptor,
                    'purpose'    => $purpose,
                    'template'   => $template,
                    'message_id' => $smsResult->messageid ?? null,
                    'status'     => $smsResult->status ?? null,
                    'statustext' => $smsResult->statustext ?? null,
                ]);

                return true;
            }

            Log::error('SMS OTP sending failed: Empty result from Kavenegar', [
                'user_id' => $user->id,
                'phone'   => $receptor,
                'purpose' => $purpose,
            ]);

            return false;

        } catch (ApiException $e) {
            Log::error('Kavenegar API error while sending SMS OTP', [
                'user_id' => $user->id,
                'phone'   => $user->phone,
                'purpose' => $purpose,
                'error'   => $e->errorMessage(),
                'code'    => $e->getCode(),
            ]);
            throw $e;

        } catch (HttpException $e) {
            Log::error('Kavenegar HTTP error while sending SMS OTP', [
                'user_id' => $user->id,
                'phone'   => $user->phone,
                'purpose' => $purpose,
                'error'   => $e->errorMessage(),
            ]);
            throw $e;

        } catch (Exception $e) {
            Log::error('Unexpected error while sending SMS OTP', [
                'user_id' => $user->id,
                'phone'   => $user->phone,
                'purpose' => $purpose,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get an SMS message for OTP based on purpose
     *
     * @deprecated این متد دیگر استفاده نمی‌شود. OTP از طریق VerifyLookup و الگوی
     *             پنل کاوه‌نگار ارسال می‌گردد. فقط در صورت نیاز به fallback نگه داشته شده.
     */
    protected function getOtpSmsMessage(string $code, string $purpose): string
    {
        $appName = config('app.name', 'اتکا');

        // Extract bare domain from FRONTEND_URL or APP_URL for WebOTP binding
        $url = config('app.frontend_url', config('app.url', ''));
        $host = parse_url($url, PHP_URL_HOST) ?: 'etka.ir';

        $purposeLabels = [
            'login_verification' => 'ورود',
            'password_reset'     => 'بازنشانی رمز عبور',
            'phone_verification' => 'تایید شماره موبایل',
            'email_verification' => 'تایید ایمیل',
        ];
        $purposeLabel = $purposeLabels[$purpose] ?? 'تایید هویت';

        // WebOTP-compatible format:
        // - Main message in Persian (user-friendly)
        // - Last line MUST be exactly: "@domain.com #CODE" (no extra text)
        //   so Android Chrome / Samsung Internet can auto-read the OTP.
        return "{$appName}: کد {$purposeLabel} شما: {$code}\nاین کد یکبار مصرف است. آن را به کسی ندهید.\n\n@{$host} #{$code}";
    }

    /**
     * High-level send method that generates and sends OTP
     */
    public function send(User $user, string $purpose, string $channel = 'email', int $expiryMinutes = 10): array
    {
        try {
            // Generate OTP
            $otpData = $this->generateOtp($user, $purpose, $channel, $expiryMinutes);

            // Send OTP
            $sent = $this->sendOtp($user, $otpData['code'], $channel, $purpose);

            if (!$sent) {
                return [
                    'success' => false,
                    'message' => 'ارسال کد تأیید ناموفق بود. لطفاً دوباره تلاش کنید.',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'کد تأیید با موفقیت ارسال شد',
                'data' => [
                    'expires_at' => $otpData['expires_at'],
                    'max_attempts' => config('iam.otp.' . $channel . '.max_attempts', 5)
                ]
            ];

        } catch (ApiException $e) {
            return [
                'success' => false,
                'message' => 'سرویس پیامک در حال حاضر در دسترس نیست. لطفاً از روش دیگری استفاده کنید یا بعداً تلاش کنید.',
                'data' => null,
                'service_error' => true,
            ];

        } catch (HttpException $e) {
            return [
                'success' => false,
                'message' => 'خطا در اتصال به سرویس پیامک. لطفاً دوباره تلاش کنید.',
                'data' => null,
                'service_error' => true,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'ارسال کد تأیید ناموفق بود. لطفاً دوباره تلاش کنید.',
                'data' => null
            ];
        }
    }
}
