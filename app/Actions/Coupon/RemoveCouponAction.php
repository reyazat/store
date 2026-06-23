<?php

namespace App\Actions\Coupon;

use App\Models\Cart;

/**
 * Remove the applied coupon from a cart and reset the discount to zero.
 */
class RemoveCouponAction
{
    public function execute(Cart $cart): void
    {
        $cart->update([
            'coupon_id'       => null,
            'coupon_discount' => 0,
        ]);
    }
}
