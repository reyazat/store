<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $coupon = $this->whenLoaded('coupon', fn() => $this->coupon);

        return [
            'id'              => $this->id,
            'items'           => CartItemResource::collection($this->whenLoaded('items')),
            'subtotal'        => (float) $this->subtotal,
            'coupon_discount' => (float) ($this->coupon_discount ?? 0),
            'total'           => (float) $this->total,
            'items_count'     => $this->items_count,
            'coupon'          => $coupon ? [
                'id'    => $coupon->id,
                'code'  => $coupon->code,
                'name'  => $coupon->name,
                'type'  => $coupon->type,
                'value' => $coupon->value,
            ] : null,
        ];
    }
}
