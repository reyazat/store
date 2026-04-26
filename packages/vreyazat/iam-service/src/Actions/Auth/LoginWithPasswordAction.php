<?php

namespace IamApi\Actions\Auth;

use IamApi\Actions\BaseAction;
use IamApi\Services\AuthService;
use IamApi\Services\TokenService;
use IamApi\Services\MfaService;

class LoginWithPasswordAction extends BaseAction
{
    public function __construct(
        protected AuthService $authService,
        protected TokenService $tokenService,
        protected MfaService $mfaService
    ) {}

    public function execute(array $data = []): array
    {
        // Get rate limit key from middleware
        $rateKey = request()->attributes->get('rate_limit_key');
        
        // Validate credentials
        $user = $this->authService->validateCredentials(
            $data['email'],
            $data['password'],
            $rateKey
        );

        if (!$user) {
            return $this->error('Invalid credentials', null, 401);
        }

        // Check if an account is locked
        if ($user->isLocked()) {
            return $this->error('Account is temporarily locked', [
                'locked_until' => $user->locked_until
            ], 423);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return $this->error('Account is disabled', null, 403);
        }

        // Check if password change is required
        if ($user->must_change_password) {
            return $this->error('Password change required', [
                'action_required' => 'change_password',
                'user_id' => $user->id
            ], 422);
        }

        // Check if MFA is enabled
        if ($this->mfaService->isMfaEnabled($user)) {
            // Generate temporary token for MFA verification
            $tempToken = $this->tokenService->issue($user, [
                'temp' => true,
                'mfa_required' => true
            ], [
                'device_name' => $data['device_name'] ?? 'Unknown Device'
            ]);

            return $this->success([
                'action_required' => 'mfa_verification',
                'temp_token' => $tempToken['access_token'],
                'mfa_methods' => ['totp', 'recovery_code']
            ], 'MFA verification required');
        }

        // Generate tokens
        $tokens = $this->tokenService->issue($user, [], [
            'device_name' => $data['device_name'] ?? 'Unknown Device'
        ]);

        // Load user organization (if exists - Super Admin doesn't have organization)
        if ($user->organization_id) {
            $user->load('organization');
        }

        // Update login info
        $this->authService->touchLogin($user, request()->ip());

        // Log successful login
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'device_name' => $data['device_name'] ?? 'Unknown Device'
            ])
            ->log('User logged in successfully');

        return $this->success([
            'user' => $user,
            'tokens' => $tokens
        ], 'Login successful');
    }
}
