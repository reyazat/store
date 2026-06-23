<?php

namespace App\Actions\Store;

use App\Actions\User\UnlockUserAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;

/**
 * Unlock a store team member's account.
 *
 * Security rules:
 * - Requires users.manage permission (owner / admin only)
 * - Target must belong to the same organisation
 * - Account must actually be locked
 */
class UnlockStoreMemberAction
{
    public function __construct(
        private readonly UnlockUserAction     $unlockUserAction,
        private readonly OrganizationService  $organizationService,
    ) {}

    /** @return array{success: bool, message: string, status?: int} */
    public function execute(Organization $org, User $actor, User $target): array
    {
        // 1. Permission
        if (!$this->organizationService->hasPermission($org, $actor, 'users.manage')) {
            return ['success' => false, 'message' => 'شما اجازه آنلاک کردن اعضا را ندارید.', 'status' => 403];
        }

        // 2. Target must be a member of this org
        if ($target->organization_id !== $org->id) {
            return ['success' => false, 'message' => 'این کاربر عضو فروشگاه شما نیست.', 'status' => 403];
        }

        // 3. Must actually be locked
        if (!$target->locked_until || $target->locked_until->isPast()) {
            return ['success' => false, 'message' => 'این حساب قفل نشده است.', 'status' => 422];
        }

        $this->unlockUserAction->execute($target, $actor);

        return ['success' => true, 'message' => 'حساب کاربر با موفقیت آنلاک شد.'];
    }
}
