<?php

namespace IamApi\Services;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;

class PasswordPolicyService
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     * Validate password against policy
     */
    public function validatePassword(string $password, User $user = null): array
    {
        $errors = [];
        $policy = config('iam.password', []);

        // Length check - use config values
        $minLength = $policy['min_length'] ?? 8;
        $maxLength = $policy['max_length'] ?? 128;
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least $minLength characters long";
        }
        
        if (strlen($password) > $maxLength) {
            $errors[] = "Password must not exceed $maxLength characters";
        }

        // Complexity checks - use config values
        if (($policy['require_uppercase'] ?? true) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (($policy['require_lowercase'] ?? true) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (($policy['require_numbers'] ?? true) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (($policy['require_symbols'] ?? true) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        // History check
        if ($user && $this->isPasswordInHistory($user, $password)) {
            $historyCount = $policy['history_count'] ?? 5;
            $errors[] = "Password cannot be one of your last $historyCount passwords";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if password exists in user's history
     */
    public function isPasswordInHistory(User $user, string $password): bool
    {
        $historyCount = config('iam.password.history_count', 5);
        return $this->userRepository->passwordExistsInHistory($user, $password, $historyCount);
    }

    /**
     * Check password history (wrapper method for actions)
     */
    public function checkPasswordHistory(User $user, string $password): array
    {
        $inHistory = $this->isPasswordInHistory($user, $password);
        
        return [
            'allowed' => !$inHistory,
            'in_history' => $inHistory
        ];
    }

    /**
     * Update password with policy enforcement
     */
    public function updatePassword(User $user, string $newPassword): array
    {
        $validation = $this->validatePassword($newPassword, $user);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Password validation failed',
                'errors' => $validation['errors']
            ];
        }

        $updated = $this->userRepository->updatePasswordWithHistory($user, $newPassword);
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
        }

        return [
            'success' => true,
            'message' => 'Password updated successfully'
        ];
    }

}
