<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'user'           => $this->whenLoaded('user', fn () => $this->user ? [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'customer_name'  => $this->user?->name,
            'status'         => $this->status,
            'payment_status' => $this->payment_status,
            'total'          => $this->total,
            'items_count'    => $this->whenLoaded('items', fn () => $this->items->count()),
            'created_at'     => $this->created_at,
        ];
    }
}
