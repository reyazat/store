<?php

namespace App\Http\Resources\Product;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read Product $resource
 * @mixin Product
 */
class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get first active listing for price and availability
        $firstListing = $this->whenLoaded('listings', function () {
            return $this->listings->where('status', 'active')->first();
        });

        // Get manufacturer name
        $manufacturer = $this->whenLoaded('manufacturer', function () {
            return $this->manufacturer?->name;
        });

        // Calculate total stock from listing variants
        $totalStock = 0;
        if ($firstListing && $firstListing->relationLoaded('listingVariants')) {
            $totalStock = $firstListing->listingVariants->where('status', 1)->sum('stock_quantity');
        }

        $originalPrice = $firstListing?->base_price ?? 0;
        $discountedPrice = $this->resource->calculateDiscountedPrice($originalPrice);
        $hasDiscount = $this->resource->hasActiveDiscount();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'base_sku' => $this->base_sku,
            'main_image' => $this->main_image,
            'manufacturer_id' => $this->manufacturer_id,
            'manufacturer' => $manufacturer,
            'tax_class_id' => $this->tax_class_id,
            'status' => $this->status,
            'sorted' => $this->sorted,
            'created_at' => $this->created_at?->toDateTimeString(),
            'listing_id' => $firstListing?->id,
            'price' => $hasDiscount ? $discountedPrice : $originalPrice,
            'old_price' => $hasDiscount ? $originalPrice : null,
            'discount_percent' => $hasDiscount ? $this->discount_percent : null,
            'discount_start_at' => $this->discount_start_at?->toDateTimeString(),
            'discount_end_at' => $this->discount_end_at?->toDateTimeString(),
            'stock' => $totalStock,
        ];
    }
}
