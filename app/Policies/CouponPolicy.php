<?php

namespace App\Policies;

use App\Models\Coupon;
use IamApi\Models\User;

class CouponPolicy
{
    /**
     * Determine if the user can view the coupon.
     */
    public function view(User $user, Coupon $coupon): bool
    {
        // Super admin can view all coupons
        if ($user->is_super_admin) {
            return true;
        }

        // Platform-level coupons (organization_id = null) are visible to all
        if ($coupon->organization_id === null) {
            return true;
        }

        // Store members can view their own store coupons
        return $user->organization_id === $coupon->organization_id;
    }

    /**
     * Determine if the user can create coupons.
     */
    public function create(User $user): bool
    {
        // Super admin can create platform-level coupons
        if ($user->is_super_admin) {
            return true;
        }

        // Store members can create store-level coupons
        return $user->organization_id !== null;
    }

    /**
     * Determine if the user can update the coupon.
     */
    public function update(User $user, Coupon $coupon): bool
    {
        // Super admin can update platform-level coupons only
        if ($user->is_super_admin) {
            return $coupon->organization_id === null;
        }

        // User must belong to the same organization as the coupon
        return $user->organization_id === $coupon->organization_id;
    }

    /**
     * Determine if the user can delete the coupon.
     */
    public function delete(User $user, Coupon $coupon): bool
    {
        // Super admin can delete platform-level coupons only
        if ($user->is_super_admin) {
            return $coupon->organization_id === null;
        }

        // User must belong to the same organization as the coupon
        return $user->organization_id === $coupon->organization_id;
    }

    /**
     * Determine if the user can restore the coupon.
     */
    public function restore(User $user, Coupon $coupon): bool
    {
        return $this->delete($user, $coupon);
    }

    /**
     * Determine if the user can permanently delete the coupon.
     */
    public function forceDelete(User $user, Coupon $coupon): bool
    {
        return $this->delete($user, $coupon);
    }
}
