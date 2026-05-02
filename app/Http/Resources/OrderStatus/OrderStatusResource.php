<?php

namespace App\Http\Resources\OrderStatus;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'key'                  => $this->key,
            'label'                => $this->label,
            'color'                => $this->color,
            'is_cancellation'      => $this->is_cancellation,
            'is_completion'        => $this->is_completion,
            'sort_order'           => $this->sort_order,
            'notify_customer'      => $this->notify_customer,
            'notification_message' => $this->notification_message,
        ];
    }
}
