<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'driver'               => $this->driver,
            'label'                => $this->label,
            'description'          => $this->description,
            'is_active'            => $this->is_active,
            'sort_order'           => $this->sort_order,
            'config'               => $this->config ?? [],
            'success_order_status' => $this->success_order_status,
            'failure_order_status' => $this->failure_order_status,
            'cancel_order_status'  => $this->cancel_order_status,
        ];
    }
}
