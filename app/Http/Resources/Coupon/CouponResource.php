<?php

namespace App\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'organization_id' => $this->organization_id,
            'is_platform'     => $this->organization_id === null,
            'code'            => $this->code,
            'name'            => $this->name,
            'description'     => $this->description,
            'type'            => $this->type,
            'value'           => $this->value,
            'min_purchase'    => $this->min_purchase,
            'max_discount'    => $this->max_discount,
            'usage_limit'     => $this->usage_limit,
            'usage_per_user'  => $this->usage_per_user,
            'used_count'      => $this->used_count,       // fix: was $this->usage_count (non-existent)
            'starts_at'       => $this->starts_at,
            'expires_at'      => $this->expires_at,
            'status'          => $this->status,
            'is_valid'        => $this->isValid(),
            'created_at'      => $this->created_at,
        ];
    }
}
