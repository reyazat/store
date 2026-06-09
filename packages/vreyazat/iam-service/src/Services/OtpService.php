<?php

namespace IamApi\Services;

use IamApi\Models\User;
use IamApi\Models\Otp;
use IamApi\Repositories\Contracts\OtpRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
     * Send OTP via email
     */
    protected function sendOtpEmail(User $user, string $code, string $purpose): bool
    {
        // TODO: Implement email sending logic
        // Mail::to($user->email)->send(new OtpMail($code, $purpose));
        return true;
    }

    /**
     * Send OTP via SMS using Kavenegar
     */
    protected function sendOtpSms(User $user, string $code, string $purpose): bool
    {
        try {
            if (!$user->phone) {
                Log::warning('Cannot send SMS OTP: User has no phone number', ['user_id' => $user->id]);
                return false;
            }

            $sender = config('services.sms.kavenegar.sender', '10004346');
            $message = $this->getOtpSmsMessage($code, $purpose);

            $result = Kavenegar::Send($sender, [$user->phone], $message);

            if ($result && count($result) > 0) {
                $smsResult = $result[0];

                Log::info('SMS OTP sent successfully', [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'purpose' => $purpose,
                    'message_id' => $smsResult->messageid ?? null,
                    'status' => $smsResult->status ?? null,
                ]);

                return true;
            }

            Log::error('SMS OTP sending failed: Empty result', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'purpose' => $purpose,
            ]);

            return false;

        } catch (ApiException $e) {
            Log::error('Kavenegar API error while sending SMS OTP', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'purpose' => $purpose,
                'error' => $e->errorMessage(),
                'code' => $e->getCode(),
            ]);
            return false;

        } catch (HttpException $e) {
            Log::error('Kavenegar HTTP error while sending SMS OTP', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'purpose' => $purpose,
                'error' => $e->errorMessage(),
            ]);
            return false;

        } catch (Exception $e) {
            Log::error('Unexpected error while sending SMS OTP', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get an SMS message for OTP based on purpose
     * Format optimized for Iranian SMS auto-fill detection
     */
    protected function getOtpSmsMessage(string $code, string $purpose): string
    {
        // Standard Persian format for Iranian platforms
        // This format helps mobile browsers auto-detect OTP codes
        $template = "کد تایید سامانه ی فروشگاه شرکت تجارت: %token\nاین کد مختص شما میباشد. آن را در اختیار دیگران قرار ندهید.";

        return str_replace('%token', $code, $template);
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
                    'message' => 'Failed to send OTP',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'data' => [
                    'expires_at' => $otpData['expires_at'],
                    'max_attempts' => config('iam.otp.' . $channel . '.max_attempts', 5)
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
