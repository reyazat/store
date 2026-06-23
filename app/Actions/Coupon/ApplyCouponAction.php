<?php

namespace App\Actions\Coupon;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use IamApi\Models\User;

/**
 * Validate and apply a coupon code to a cart.
 *
 * Business rules:
 * - Coupon must exist, be active, within date range, and under usage limit
 * - min_purchase: cart subtotal (or store-scoped subtotal) must meet the minimum
 * - usage_per_user: user cannot exceed their personal usage cap
 * - Store-level coupon (organization_id set): cart must contain at least one product
 *   from that store; discount is applied only to those items
 * - Platform-level coupon (organization_id null): applies to the full cart subtotal
 */
class ApplyCouponAction
{
    /** @return array{success: bool, message: string, discount?: float, coupon?: array} */
    public function execute(Cart $cart, User $user, string $code): array
    {
        // Ensure items and their product/org relations are loaded
        $cart->loadMissing(['items.listing.product', 'items.coupon']);

        $coupon = Coupon::where('code', strtoupper(trim($code)))
            ->whereNull('deleted_at')
            ->first();

        if (!$coupon) {
            return ['success' => false, 'message' => 'کد کوپن معتبر نیست.'];
        }

        if (!$coupon->isValid()) {
            if (!$coupon->status) {
                return ['success' => false, 'message' => 'این کوپن غیرفعال است.'];
            }
            if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
                return ['success' => false, 'message' => 'این کوپن منقضی شده است.'];
            }
            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                return ['success' => false, 'message' => 'سقف استفاده از این کوپن تمام شده است.'];
            }
            return ['success' => false, 'message' => 'کوپن فعال نیست یا تاریخ اعتبار آن فرا نرسیده است.'];
        }

        // Per-user usage cap
        if ($coupon->usage_per_user) {
            $used = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();
            if ($used >= $coupon->usage_per_user) {
                return ['success' => false, 'message' => 'شما قبلاً از این کوپن استفاده کرده‌اید.'];
            }
        }

        // Determine applicable items and subtotal
        if ($coupon->isStoreLevel()) {
            $applicableItems = $cart->items->filter(
                fn($item) => $item->listing?->product?->organization_id === $coupon->organization_id
            );

            if ($applicableItems->isEmpty()) {
                return ['success' => false, 'message' => 'این کوپن فقط برای محصولات این فروشگاه قابل استفاده است.'];
            }

            $applicableSubtotal = $applicableItems->sum(fn($i) => (float) $i->price * $i->quantity);
        } else {
            // Platform-level: applies to the full cart
            $applicableSubtotal = $cart->items->sum(fn($i) => (float) $i->price * $i->quantity);
        }

        // Minimum purchase check
        if ($coupon->min_purchase && $applicableSubtotal < (float) $coupon->min_purchase) {
            return [
                'success' => false,
                'message' => 'حداقل مبلغ خرید برای استفاده از این کوپن ' .
                    number_format((float) $coupon->min_purchase) . ' تومان است.',
            ];
        }

        $discount = $coupon->calculateDiscount($applicableSubtotal);

        $cart->update([
            'coupon_id'       => $coupon->id,
            'coupon_discount' => $discount,
        ]);

        return [
            'success'  => true,
            'message'  => 'کوپن با موفقیت اعمال شد.',
            'discount' => $discount,
            'coupon'   => [
                'id'    => $coupon->id,
                'code'  => $coupon->code,
                'name'  => $coupon->name,
                'type'  => $coupon->type,
                'value' => $coupon->value,
            ],
        ];
    }
}
