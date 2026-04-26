<?php

namespace IamApi\Actions\Account;

use IamApi\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class LockAccountAction
{
    /**
     * Execute the lock account action
     */
    public function execute(array $data): array
    {
        /** @var User $adminUser */
        $adminUser = $data['admin_user'];
        $userId = $data['user_id'];
        $reason = $data['reason'];
        $durationMinutes = $data['duration_minutes'];

        try {
            // Find the target user
            $user = User::query()->find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found.',
                    'code' => 404
                ];
            }

            // Check if an account is already locked
            if ($user->locked_until && $user->locked_until->isFuture()) {
                return [
                    'success' => false,
                    'message' => 'Account is already locked.',
                    'code' => 400,
                    'data' => [
                        'locked_until' => $user->locked_until->toISOString()
                    ]
                ];
            }

            // Prevent locking Super Admin accounts (unless by another Super Admin)
            if ($user->isSuperAdmin() && !$adminUser->isSuperAdmin()) {
                return [
                    'success' => false,
                    'message' => 'Cannot lock Super Admin accounts.',
                    'code' => 403
                ];
            }

            // Calculate lock expiry time
            $lockedUntil = Carbon::now()->addMinutes($durationMinutes);

            // Lock the account
            $user->update([
                'locked_until' => $lockedUntil,
                'failed_login_attempts' => config('iam.authentication.lockout.max_attempts', 10)
            ]);

            // Log the admin lock action
            activity()
                ->performedOn($user)
                ->causedBy($adminUser)
                ->withProperties([
                    'reason' => $reason,
                    'duration_minutes' => $durationMinutes,
                    'locked_until' => $lockedUntil->toISOString(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('Account locked by admin');

            // Also log from the target user's perspective
            activity()
                ->performedOn($user)
                ->withProperties([
                    'locked_by' => $adminUser->email,
                    'reason' => $reason,
                    'duration_minutes' => $durationMinutes,
                    'locked_until' => $lockedUntil->toISOString()
                ])
                ->log('Account locked by administrator');

            return [
                'success' => true,
                'message' => 'Account locked successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'locked_at' => now()->toISOString(),
                    'locked_until' => $lockedUntil->toISOString(),
                    'duration_minutes' => $durationMinutes,
                    'reason' => $reason,
                    'locked_by' => $adminUser->email,
                    'locked_by_id' => $adminUser->id
                ]
            ];

        } catch (Exception $e) {
            Log::error('Lock account failed', [
                'admin_user_id' => $adminUser->id,
                'target_user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to lock account. Please try again later.',
                'code' => 500
            ];
        }
    }
}
