<?php

namespace App\Policies;

use App\Models\Coupon;
use IamApi\Models\User;
use IamApi\Services\OrganizationService;

class CouponPolicy
{
    public function __construct(private readonly OrganizationService $organizationService)
    {
    }

    /**
     * Determine if the user can view the coupon.
     * Viewing is permissive: super admin, platform coupons are visible to all,
     * store coupons are visible to members of that org.
     */
    public function view(User $user, Coupon $coupon): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        // Platform-level coupons: visible to any authenticated user (e.g. to display discount at checkout)
        if ($coupon->organization_id === null) {
            return true;
        }

        return $user->organization_id === $coupon->organization_id;
    }

    /**
     * Determine if the user can create coupons.
     * - Super Admin: can create platform-level coupons only (enforced in CouponController)
     * - Store member with settings.manage: can create store-level coupons
     */
    public function create(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if (!$user->organization_id) {
            return false;
        }

        $org = $user->organization;
        if (!$org) {
            return false;
        }

        return $this->organizationService->hasPermission($org, $user, 'settings.manage');
    }

    /**
     * Determine if the user can update the coupon.
     * - Super Admin: only platform-level coupons
     * - Store member: must have settings.manage and own the coupon
     */
    public function update(User $user, Coupon $coupon): bool
    {
        if ($user->is_super_admin) {
            return $coupon->organization_id === null;
        }

        if ($user->organization_id !== $coupon->organization_id) {
            return false;
        }

        $org = $user->organization;
        return $org && $this->organizationService->hasPermission($org, $user, 'settings.manage');
    }

    /**
     * Determine if the user can delete the coupon.
     */
    public function delete(User $user, Coupon $coupon): bool
    {
        if ($user->is_super_admin) {
            return $coupon->organization_id === null;
        }

        if ($user->organization_id !== $coupon->organization_id) {
            return false;
        }

        $org = $user->organization;
        return $org && $this->organizationService->hasPermission($org, $user, 'settings.manage');
    }

    public function restore(User $user, Coupon $coupon): bool
    {
        return $this->delete($user, $coupon);
    }

    public function forceDelete(User $user, Coupon $coupon): bool
    {
        return $this->delete($user, $coupon);
    }
}
