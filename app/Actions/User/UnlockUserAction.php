<?php

namespace App\Actions\User;

use IamApi\Models\User;

class UnlockUserAction
{
    public function execute(User $targetUser, User $adminUser): void
    {
        $targetUser->update([
            'locked_until'          => null,
            'failed_login_attempts' => 0,
        ]);

        activity()
            ->performedOn($targetUser)
            ->causedBy($adminUser)
            ->withProperties([
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Account unlocked by admin');
    }
}
