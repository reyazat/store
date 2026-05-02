<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusModel = \App\Models\OrderStatus::findByKey($this->status);

        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'user'           => $this->whenLoaded('user', fn () => $this->user ? [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'status'         => $this->status,
            'status_label'   => $statusModel?->label ?? $this->status,
            'status_color'   => $statusModel?->color ?? '#6b7280',
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal'       => $this->subtotal,
            'tax'            => $this->tax,
            'shipping'       => $this->shipping,
            'discount'       => $this->discount,
            'total'          => $this->total,
            'notes'          => $this->notes,
            'admin_notes'    => $this->admin_notes,
            'items'          => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'vendor_name'  => $item->vendor_name,
                'sku'          => $item->sku,
                'quantity'     => $item->quantity,
                'price'        => $item->price,
                'subtotal'     => $item->subtotal,
            ])),
            'address'        => $this->whenLoaded('orderAddress', fn () => $this->orderAddress ? [
                'first_name'  => $this->orderAddress->first_name,
                'last_name'   => $this->orderAddress->last_name,
                'phone'       => $this->orderAddress->phone,
                'address'     => $this->orderAddress->address,
                'city'        => $this->orderAddress->city,
                'province'    => $this->orderAddress->province,
                'postal_code' => $this->orderAddress->postal_code,
            ] : null),
            'latest_transaction' => $this->whenLoaded('latestTransaction', fn () => $this->latestTransaction ? [
                'id'             => $this->latestTransaction->id,
                'driver'         => $this->latestTransaction->driver,
                'gateway_ref'    => $this->latestTransaction->gateway_ref,
                'status'         => $this->latestTransaction->status,
                'amount'         => $this->latestTransaction->amount,
                'card_last_four' => $this->latestTransaction->card_last_four,
                'receipt_path'   => $this->latestTransaction->receipt_path
                    ? asset('storage/' . $this->latestTransaction->receipt_path)
                    : null,
                'paid_at'        => $this->latestTransaction->paid_at,
            ] : null),
            'created_at'     => $this->created_at,
            'paid_at'        => $this->paid_at,
            'shipped_at'     => $this->shipped_at,
            'delivered_at'   => $this->delivered_at,
            'cancelled_at'   => $this->cancelled_at,
        ];
    }
}
