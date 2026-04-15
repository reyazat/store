<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
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
        $attributes = ['status' => $status];

        if ($status === 'shipped') {
            $attributes['shipped_at'] = now();
        } elseif ($status === 'delivered') {
            $attributes['delivered_at'] = now();
        } elseif ($status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $order->update($attributes);

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
}
