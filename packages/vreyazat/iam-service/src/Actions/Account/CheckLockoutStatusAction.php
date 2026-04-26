<?php

namespace IamApi\Actions\Account;

use IamApi\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class CheckLockoutStatusAction
{
    /**
     * Execute the check lockout status action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];

        try {
            $now = Carbon::now();
            $isLocked = $user->locked_until && $user->locked_until->isFuture();

            // Get lockout configuration
            $lockoutConfig = config('iam.authentication.lockout', []);
            $maxAttempts = $lockoutConfig['max_attempts'] ?? 10;
            $lockoutDuration = $lockoutConfig['duration_minutes'] ?? 30;

            // Calculate remaining attempts
            $remainingAttempts = max(0, $maxAttempts - $user->failed_login_attempts);

            // Calculate unlock time if locked
            $unlockInSeconds = null;
            if ($isLocked) {
                $unlockInSeconds = $user->locked_until->diffInSeconds($now);
            }

            // Determine a message
            $message = $isLocked
                ? 'Account is currently locked'
                : 'Account lockout status retrieved';

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'is_locked' => $isLocked,
                    'locked_until' => $user->locked_until?->toISOString(),
                    'failed_login_attempts' => $user->failed_login_attempts,
                    'max_attempts' => $maxAttempts,
                    'remaining_attempts' => $remainingAttempts,
                    'lockout_duration_minutes' => $lockoutDuration,
                    'last_failed_attempt' => $this->getLastFailedAttempt($user),
                    'can_attempt_login' => !$isLocked,
                    'unlock_in_seconds' => $unlockInSeconds,
                    'lockout_reason' => $this->getLockoutReason($user),
                    'progressive_lockout' => $this->getProgressiveLockoutInfo($user->failed_login_attempts)
                ]
            ];

        } catch (Exception $e) {
            Log::error('Check lockout status failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check lockout status. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Get the last failed attempt timestamp
     */
    private function getLastFailedAttempt(User $user): ?string
    {
        // This would typically come from activity logs
        $lastFailedActivity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('description', 'Failed login attempt')
            ->latest()
            ->first();

        return $lastFailedActivity?->created_at?->toISOString();
    }

    /**
     * Get lockout reason
     */
    private function getLockoutReason(User $user): ?string
    {
        if (!$user->locked_until) {
            return null;
        }

        if ($user->failed_login_attempts >= config('iam.authentication.lockout.max_attempts', 10)) {
            return 'exceeded_failed_attempts';
        }

        // Check if locked by admin
        $adminLockActivity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('description', 'Account locked by admin')
            ->latest()
            ->first();

        if ($adminLockActivity && $adminLockActivity->created_at->isAfter($user->locked_until->subMinutes(config('iam.authentication.lockout.duration_minutes', 30)))) {
            return 'admin_locked';
        }

        return 'security_policy';
    }

    /**
     * Get progressive lockout information
     */
    private function getProgressiveLockoutInfo(int $failedAttempts): array
    {
        $thresholds = config('iam.authentication.lockout.thresholds', [
            ['attempts' => 3, 'duration_minutes' => 1],
            ['attempts' => 5, 'duration_minutes' => 5],
            ['attempts' => 7, 'duration_minutes' => 15],
            ['attempts' => 10, 'duration_minutes' => 30]
        ]);

        $currentThreshold = null;
        $nextThreshold = null;

        foreach ($thresholds as $threshold) {
            if ($failedAttempts >= $threshold['attempts']) {
                $currentThreshold = $threshold;
            } elseif (!$nextThreshold) {
                $nextThreshold = $threshold;
                break;
            }
        }

        return [
            'enabled' => config('iam.authentication.lockout.progressive', true),
            'current_threshold' => $currentThreshold,
            'next_threshold' => $nextThreshold,
            'thresholds' => $thresholds
        ];
    }
}
