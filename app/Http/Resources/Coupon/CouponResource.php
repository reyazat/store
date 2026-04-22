<?php

namespace App\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'name'           => $this->name,
            'description'    => $this->description,
            'type'           => $this->type,
            'value'          => $this->value,
            'min_purchase'   => $this->min_purchase,
            'max_discount'   => $this->max_discount,
            'usage_limit'    => $this->usage_limit,
            'usage_per_user' => $this->usage_per_user,
            'usage_count'    => $this->usage_count,
            'starts_at'      => $this->starts_at,
            'expires_at'     => $this->expires_at,
            'status'         => $this->status,
            'created_at'     => $this->created_at,
        ];
    }
}
