<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'status'         => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal'       => $this->subtotal,
            'tax'            => $this->tax,
            'shipping'       => $this->shipping,
            'discount'       => $this->discount,
            'total'          => $this->total,
            'notes'          => $this->notes,
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
            'created_at'     => $this->created_at,
            'paid_at'        => $this->paid_at,
            'shipped_at'     => $this->shipped_at,
            'delivered_at'   => $this->delivered_at,
        ];
    }
}
