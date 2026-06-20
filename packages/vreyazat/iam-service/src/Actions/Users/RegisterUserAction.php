<?php

namespace IamApi\Actions\Users;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\OtpService;
use IamApi\Services\PasswordPolicyService;
use Exception;
use Illuminate\Support\Facades\Log;

class RegisterUserAction extends BaseAction
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected OtpService $otpService,
        protected PasswordPolicyService $passwordPolicyService
    ) {}

    public function execute(array $data = []): array
    {
        // Validate password against policy
        $passwordValidation = $this->passwordPolicyService->validatePassword($data['password']);
        if (!$passwordValidation['valid']) {
            return $this->error('Password validation failed', [
                'password_errors' => $passwordValidation['errors']
            ], 422);
        }

        try {
            // Create user with no organization (buyers are marketplace customers,
            // not members of any store. organization_id stays null until a store owner
            // explicitly invites / assigns them to a store organization.)
            /** @var User $user */
            $user = $this->userRepository->create([
                'name'                 => $data['name'],
                'email'                => $data['email'],
                'phone'                => $data['phone'] ?? null,
                'password'             => $data['password'],
                'status'               => 'active',
                'must_change_password' => false,
                'password_changed_at'  => now(),
                'password_expires_at'  => now()->addDays(config('iam.password.expiry_days', 90)),
            ]);

            // Send email verification if requested
            $emailVerificationSent = false;
            if ($data['send_verification_email'] ?? true) {
                try {
                    $otpData = $this->otpService->generateOtp($user, 'email_verification', 'email', 60);
                    $emailVerificationSent = $this->otpService->sendOtp(
                        $user,
                        $otpData['code'],
                        'email',
                        'email_verification'
                    );
                } catch (Exception $e) {
                    // Don't fail registration if email sending fails
                    Log::error($e->getMessage());
                }
            }

            // Log user registration
            activity()
                ->performedOn($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'email_verification_sent' => $emailVerificationSent
                ])
                ->log('User registered successfully');

            return $this->success([
                'user'                    => $user,
                'email_verification_sent' => $emailVerificationSent,
            ], 'User registered successfully');

        } catch (Exception $e) {
            return $this->error('Registration failed: ' . $e->getMessage(), null, 500);
        }
    }
}
