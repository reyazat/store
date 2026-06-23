<?php

namespace App\Actions\Store;

use App\Actions\User\LockUserAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;

/**
 * Lock a store team member's account.
 *
 * Security rules:
 * - Requires users.manage permission (owner / admin only)
 * - Actor cannot lock themselves
 * - Actor cannot lock another owner unless actor is also owner
 * - Target must belong to the same organisation
 * - Super admin accounts can never be locked via this action
 * - Account already locked: returns a 422 with current expiry
 */
class LockStoreMemberAction
{
    public function __construct(
        private readonly LockUserAction       $lockUserAction,
        private readonly OrganizationService  $organizationService,
    ) {}

    /**
     * @param int $durationMinutes Use PHP_INT_MAX / very large value for "permanent"
     * @return array{success: bool, message: string, status?: int}
     */
    public function execute(
        Organization $org,
        User         $actor,
        User         $target,
        string       $reason,
        int          $durationMinutes
    ): array {
        // 1. Permission
        if (!$this->organizationService->hasPermission($org, $actor, 'users.manage')) {
            return ['success' => false, 'message' => 'شما اجازه قفل کردن اعضا را ندارید.', 'status' => 403];
        }

        // 2. Cannot lock yourself
        if ($actor->id === $target->id) {
            return ['success' => false, 'message' => 'نمی‌توانید حساب خود را قفل کنید.', 'status' => 422];
        }

        // 3. Target must be a member of this org
        if ($target->organization_id !== $org->id) {
            return ['success' => false, 'message' => 'این کاربر عضو فروشگاه شما نیست.', 'status' => 403];
        }

        // 4. Never lock super admins (belt & suspenders — middleware already blocks them)
        if ($target->is_super_admin) {
            return ['success' => false, 'message' => 'امکان قفل کردن حساب سوپر ادمین وجود ندارد.', 'status' => 403];
        }

        // 5. Hierarchy: admin cannot lock an owner
        $actorRole  = $this->organizationService->getUserRole($org, $actor);
        $targetRole = $this->organizationService->getUserRole($org, $target);

        if ($targetRole === 'owner' && $actorRole !== 'owner') {
            return ['success' => false, 'message' => 'مدیر نمی‌تواند حساب مالک فروشگاه را قفل کند.', 'status' => 403];
        }

        // 6. Already locked?
        if ($target->locked_until && $target->locked_until->isFuture()) {
            return [
                'success'     => false,
                'message'     => 'این حساب از قبل قفل شده است.',
                'locked_until'=> $target->locked_until->toISOString(),
                'status'      => 422,
            ];
        }

        $this->lockUserAction->execute($target, $actor, $reason, $durationMinutes);

        return ['success' => true, 'message' => 'حساب کاربر با موفقیت قفل شد.'];
    }
}
