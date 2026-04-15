<?php

namespace App\Http\Resources\Listing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $originalPrice = $this->base_price ?? 0;
        $hasDiscount = $this->product?->hasActiveDiscount() ?? false;
        $discountedPrice = $hasDiscount
            ? $this->product->calculateDiscountedPrice($originalPrice)
            : $originalPrice;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'vendor_id' => $this->vendor_id,
            'vendor_name' => $this->vendor?->name,
            'status' => $this->status,
            'base_price' => $hasDiscount ? $discountedPrice : $originalPrice,
            'original_price' => $hasDiscount ? $originalPrice : null,
            'discount_percent' => $hasDiscount ? $this->product->discount_percent : null,
            'min_order_qty' => $this->min_order_qty,
            'created_at' => $this->created_at,
        ];
    }
}
