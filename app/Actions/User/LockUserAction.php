<?php

namespace App\Actions\User;

use Carbon\Carbon;
use IamApi\Models\User;

class LockUserAction
{
    public function execute(User $targetUser, User $adminUser, string $reason, int $durationMinutes): void
    {
        $lockedUntil = Carbon::now()->addMinutes($durationMinutes);

        $targetUser->update([
            'locked_until'          => $lockedUntil,
            'failed_login_attempts' => config('iam.authentication.lockout.max_attempts', 10),
        ]);

        activity()
            ->performedOn($targetUser)
            ->causedBy($adminUser)
            ->withProperties([
                'reason'           => $reason,
                'duration_minutes' => $durationMinutes,
                'locked_until'     => $lockedUntil->toISOString(),
                'ip'               => request()->ip(),
                'user_agent'       => request()->userAgent(),
            ])
            ->log('Account locked by admin');
    }
}
