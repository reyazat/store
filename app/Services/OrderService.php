<?php

namespace App\Services;

use App\Actions\Order\SendOrderNotificationAction;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\ListingVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SendOrderNotificationAction $sendNotificationAction,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function createOrderFromCart(User $user, Cart $cart, Address $address, array $data = []): Order
    {
        return DB::transaction(function () use ($user, $cart, $address, $data) {
            $subtotal = 0;

            // Pre-validate stock and lock rows to prevent oversell
            $this->assertStockAvailable($cart);

            // Resolve coupon discount from the cart (never from raw $data to prevent injection)
            $cart->loadMissing('coupon');
            $appliedCoupon   = ($cart->coupon_id && $cart->coupon?->isValid()) ? $cart->coupon : null;
            $couponDiscount  = $appliedCoupon ? (float) $cart->coupon_discount : 0.0;

            $order = Order::create([
                'user_id'        => $user->id,
                'address_id'     => $address->id,
                'status'         => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'subtotal'       => 0,
                'tax'            => 0,
                'shipping'       => $data['shipping'] ?? 0,
                'discount'       => $couponDiscount,
                'total'          => 0,
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                if (!$item->listing) {
                    continue;
                }

                $itemSubtotal = $item->price * $item->quantity;
                $subtotal += $itemSubtotal;

                $order->items()->create([
                    'listing_id'         => $item->listing_id,
                    'listing_variant_id' => $item->listing_variant_id,
                    'product_id'         => $item->listing->product_id,
                    'vendor_id'          => $item->listing->vendor_id,
                    'product_name'       => $item->listing->product?->name ?? 'محصول حذف‌شده',
                    'vendor_name'        => $item->listing->vendor?->name ?? 'فروشنده حذف‌شده',
                    'sku'                => $item->listing->product?->base_sku,
                    'quantity'           => $item->quantity,
                    'price'              => $item->price,
                    'tax'                => 0,
                    'subtotal'           => $itemSubtotal,
                ]);

                $this->decrementStock($item->listing_variant_id, (int) $item->quantity);
            }

            $tax   = $subtotal * 0.09;
            $total = max(0, $subtotal + $tax + $order->shipping - $order->discount);

            $order->update([
                'subtotal' => $subtotal,
                'tax'      => $tax,
                'total'    => $total,
            ]);

            $order->orderAddress()->create([
                'first_name'  => $address->first_name,
                'last_name'   => $address->last_name,
                'phone'       => $address->phone,
                'email'       => $address->email,
                'country'     => $address->country,
                'province'    => $address->province,
                'city'        => $address->city,
                'address'     => $address->address,
                'postal_code' => $address->postal_code,
            ]);

            // Record coupon usage, increment counter, and clear from cart
            if ($appliedCoupon) {
                CouponUsage::create([
                    'coupon_id'       => $appliedCoupon->id,
                    'user_id'         => $user->id,
                    'order_id'        => $order->id,
                    'discount_amount' => $couponDiscount,
                ]);
                $appliedCoupon->increment('used_count');
                $cart->update(['coupon_id' => null, 'coupon_discount' => 0]);
            }

            $cart->items()->delete();

            return $order->fresh(['items', 'orderAddress']);
        });
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        $previousStatus = $order->status;

        // Bug 3 fix: skip if status hasn't actually changed
        if ($previousStatus === $status) {
            return $order;
        }

        $statusModel = OrderStatus::findByKey($status);
        if (!$statusModel) {
            throw new RuntimeException("وضعیت '{$status}' یافت نشد.");
        }

        $previousStatusModel = OrderStatus::findByKey($previousStatus);

        // Prevent regression: cannot go back from terminal statuses (completion/cancellation)
        if ($previousStatusModel?->is_completion || $previousStatusModel?->is_cancellation) {
            throw new RuntimeException(
                'امکان تغییر وضعیت از ' . ($previousStatusModel?->label ?? $previousStatus) .
                ' به ' . $statusModel->label . ' وجود ندارد. سفارش در وضعیت پایانی قرار دارد.'
            );
        }

        $attributes  = ['status' => $status];

        // Bug 1 fix: set shipped_at when transitioning to a shipping status
        if ($statusModel?->is_shipping && !$order->shipped_at) {
            $attributes['shipped_at'] = now();
        }

        if ($statusModel?->is_completion && !$order->delivered_at) {
            $attributes['delivered_at'] = now();
        } elseif ($statusModel?->is_cancellation && !$order->cancelled_at) {
            $attributes['cancelled_at'] = now();
        }

        $order->update($attributes);

        $order->recordHistory(
            'status_changed',
            $previousStatus,
            $status,
        );

        // B1 fix: only restore stock on the FIRST cancellation transition.
        // Guard with cancelled_at: it was set above only when !$order->cancelled_at,
        // meaning if cancelled_at was already set (re-cancellation), we skip restore.
        // We use the fresh attributes array to detect first-time cancellation.
        $isFirstCancellation = $statusModel?->is_cancellation && isset($attributes['cancelled_at']);
        if ($isFirstCancellation) {
            $this->restoreStock($order);
        }

        // Automatic notification: if the new status has notify_customer enabled,
        // send notification to the customer using the configured message.
        if ($statusModel?->notify_customer) {
            $this->sendNotificationAction->execute($order, $statusModel->notification_message);
        }

        return $order;
    }

    /**
     * Update admin notes on an order.
     * Kept in Service (not Action) as it is a single atomic field update with no side-effects.
     */
    public function updateAdminNotes(Order $order, ?string $notes): Order
    {
        $order->update(['admin_notes' => $notes]);
        return $order;
    }

    public function updatePaymentStatus(Order $order, string $paymentStatus): Order
    {
        $previousPaymentStatus = $order->payment_status;

        $attributes = ['payment_status' => $paymentStatus];

        if ($paymentStatus === 'paid' && !$order->paid_at) {
            $attributes['paid_at'] = now();
        }

        $order->update($attributes);

        // Bug 9 fix: record history for payment status change
        if ($previousPaymentStatus !== $paymentStatus) {
            $order->recordHistory(
                'payment_status_changed',
                $previousPaymentStatus,
                $paymentStatus,
            );
        }

        return $order;
    }

    /**
     * Lock stock rows and ensure sufficient quantity for all cart items.
     */
    private function assertStockAvailable(Cart $cart): void
    {
        $variantQuantities = [];
        $listingQuantities = [];

        foreach ($cart->items as $item) {
            if (!$item->listing) {
                continue;
            }

            if ($item->listing_variant_id) {
                $variantQuantities[$item->listing_variant_id] =
                    ($variantQuantities[$item->listing_variant_id] ?? 0) + (int) $item->quantity;
            } else {
                $listingQuantities[$item->listing_id] =
                    ($listingQuantities[$item->listing_id] ?? 0) + (int) $item->quantity;
            }
        }

        if (!empty($variantQuantities)) {
            $variants = ListingVariant::whereIn('id', array_keys($variantQuantities))
                ->where('status', 1)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($variantQuantities as $variantId => $qty) {
                $variant = $variants->get($variantId);
                if (!$variant || $variant->stock_quantity < $qty) {
                    throw new RuntimeException('موجودی برخی از محصولات کافی نیست');
                }
            }
        }

        foreach ($listingQuantities as $listingId => $qty) {
            $total = (int) ListingVariant::where('listing_id', $listingId)
                ->where('status', 1)
                ->lockForUpdate()
                ->sum('stock_quantity');

            if ($total < $qty) {
                throw new RuntimeException('موجودی برخی از محصولات کافی نیست');
            }
        }
    }

    /**
     * Safely decrement stock for a specific listing variant.
     */
    private function decrementStock(?int $listingVariantId, int $quantity): void
    {
        if (!$listingVariantId || $quantity <= 0) {
            return;
        }

        ListingVariant::where('id', $listingVariantId)
            ->where('stock_quantity', '>=', $quantity)
            ->decrement('stock_quantity', $quantity);
    }

    /**
     * Restore stock for a cancelled order.
     */
    public function restoreStock(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (!$item->listing_variant_id) {
                continue;
            }
            ListingVariant::where('id', $item->listing_variant_id)
                ->increment('stock_quantity', (int) $item->quantity);
        }
    }
}
