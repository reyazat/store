<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
        ];
    }
}
