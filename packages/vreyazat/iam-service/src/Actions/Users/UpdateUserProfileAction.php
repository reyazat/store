<?php

namespace IamApi\Actions\Users;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OtpService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UpdateUserProfileAction extends BaseAction
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected OtpService $otpService
    ) {}

    public function execute(array $data = []): array
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return $this->error('User not authenticated', null, 401);
        }

        $originalEmail = $user->email;
        $originalPhone = $user->phone;

        try {
            // Prepare update data
            $updateData = [];
            $emailChanged = false;
            $phoneChanged = false;

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['email']) && $data['email'] !== $originalEmail) {
                $updateData['email'] = $data['email'];
                $updateData['email_verified_at'] = null; // Reset email verification
                $emailChanged = true;
            }

            if (isset($data['phone']) && $data['phone'] !== $originalPhone) {
                $updateData['phone'] = $data['phone'];
                $updateData['phone_verified_at'] = null; // Reset phone verification
                $phoneChanged = true;
            }

            if (empty($updateData)) {
                return $this->error('No changes detected');
            }

            // Update user
            $this->userRepository->update($user, $updateData);
            $user = $user->fresh();

            // Send verification for new email
            $emailVerificationSent = false;
            if ($emailChanged) {
                try {
                    $otpData = $this->otpService->generateOtp($user, 'email_verification', 'email', 60);
                    $emailVerificationSent = $this->otpService->sendOtp(
                        $user,
                        $otpData['code'],
                        'email',
                        'email_verification'
                    );
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }

            // Send verification for new phone
            $phoneVerificationSent = false;
            if ($phoneChanged && $user->phone) {
                try {
                    $otpData = $this->otpService->generateOtp($user, 'phone_verification', 'sms', 60);
                    $phoneVerificationSent = $this->otpService->sendOtp(
                        $user,
                        $otpData['code'],
                        'sms',
                        'phone_verification'
                    );
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }

            // Log profile update
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'changes' => $updateData,
                    'email_changed' => $emailChanged,
                    'phone_changed' => $phoneChanged,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('User profile updated');

            // Hide sensitive fields from response
            $user->makeHidden([
                'password',
                'remember_token',
                'mfa_enabled',
                'failed_login_attempts',
                'locked_until',
                'last_login_at',
                'last_login_ip',
                'password_changed_at',
                'password_expires_at',
                'must_change_password'
            ]);

            return $this->success([
                'user' => $user,
                'verifications' => [
                    'email_verification_sent' => $emailVerificationSent,
                    'phone_verification_sent' => $phoneVerificationSent
                ]
            ], 'Profile updated successfully');

        } catch (Exception $e) {
            return $this->error('Profile update failed: ' . $e->getMessage(), null, 500);
        }
    }
}
