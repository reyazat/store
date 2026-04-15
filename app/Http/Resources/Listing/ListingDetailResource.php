<?php

namespace App\Http\Resources\Listing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingDetailResource extends JsonResource
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
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'slug' => $this->product?->slug,
                'main_image' => $this->product?->main_image,
                'discount_percent' => $this->product?->discount_percent,
                'discount_start_at' => $this->product?->discount_start_at,
                'discount_end_at' => $this->product?->discount_end_at,
            ],
            'vendor_id' => $this->vendor_id,
            'vendor' => [
                'id' => $this->vendor?->id,
                'name' => $this->vendor?->name,
                'slug' => $this->vendor?->slug,
            ],
            'status' => $this->status,
            'base_price' => $hasDiscount ? $discountedPrice : $originalPrice,
            'original_price' => $hasDiscount ? $originalPrice : null,
            'discount_percent' => $hasDiscount ? $this->product->discount_percent : null,
            'min_order_qty' => $this->min_order_qty,
            'variants' => $this->listingVariants->map(fn($v) => [
                'id' => $v->id,
                'product_variant_id' => $v->product_variant_id,
                'variant_sku' => $v->variant?->sku,
                'price' => $v->price,
                'stock' => $v->stock,
                'status' => $v->status,
            ]),
            'customer_group_prices' => $this->customerGroupPrices->map(fn($p) => [
                'id' => $p->id,
                'customer_group_id' => $p->customer_group_id,
                'customer_group_name' => $p->customerGroup?->name,
                'price' => $p->price,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
