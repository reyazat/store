<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
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
    public function __construct(private readonly OrderRepositoryInterface $orderRepository)
    {
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

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'subtotal' => 0,
                'tax' => 0,
                'shipping' => $data['shipping'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                if (!$item->listing) {
                    continue;
                }

                $itemSubtotal = $item->price * $item->quantity;
                $subtotal += $itemSubtotal;

                $order->items()->create([
                    'listing_id' => $item->listing_id,
                    'listing_variant_id' => $item->listing_variant_id,
                    'product_id' => $item->listing->product_id,
                    'vendor_id' => $item->listing->vendor_id,
                    'product_name' => $item->listing->product?->name ?? 'محصول حذف‌شده',
                    'vendor_name' => $item->listing->vendor?->name ?? 'فروشنده حذف‌شده',
                    'sku' => $item->listing->product?->base_sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'tax' => 0,
                    'subtotal' => $itemSubtotal,
                ]);

                $this->decrementStock($item->listing_variant_id, (int) $item->quantity);
            }

            $tax = $subtotal * 0.09;
            $total = $subtotal + $tax + $order->shipping - $order->discount;
            
            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);
            
            $order->orderAddress()->create([
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'phone' => $address->phone,
                'email' => $address->email,
                'country' => $address->country,
                'province' => $address->province,
                'city' => $address->city,
                'address' => $address->address,
                'postal_code' => $address->postal_code,
            ]);
            
            $cart->items()->delete();
            
            return $order->fresh(['items', 'orderAddress']);
        });
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        $statusModel    = OrderStatus::findByKey($status);
        $previousStatus = $order->status;
        $attributes     = ['status' => $status];

        if ($statusModel?->is_completion) {
            $attributes['delivered_at'] = now();
        } elseif ($statusModel?->is_cancellation) {
            $attributes['cancelled_at'] = now();
        }

        $order->update($attributes);

        $previousStatusModel = OrderStatus::findByKey($previousStatus);
        if ($statusModel?->is_cancellation && !($previousStatusModel?->is_cancellation)) {
            $this->restoreStock($order);
        }

        return $order;
    }

    public function updatePaymentStatus(Order $order, string $paymentStatus): Order
    {
        $attributes = ['payment_status' => $paymentStatus];

        if ($paymentStatus === 'paid') {
            $attributes['paid_at'] = now();
        }

        $order->update($attributes);

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
        DB::transaction(function () use ($order) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                if (!$item->listing_variant_id) {
                    continue;
                }
                ListingVariant::where('id', $item->listing_variant_id)
                    ->increment('stock_quantity', (int) $item->quantity);
            }
        });
    }
}
