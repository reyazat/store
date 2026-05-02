<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusModel = \App\Models\OrderStatus::findByKey($this->status);

        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'status'         => $this->status,
            'status_label'   => $statusModel?->label ?? $this->status,
            'status_color'   => $statusModel?->color ?? '#6b7280',
            'payment_status' => $this->payment_status,
            'total'          => $this->total,
            'items_count'    => $this->whenLoaded('items', fn () => $this->items->count()),
            'created_at'     => $this->created_at,
        ];
    }
}
