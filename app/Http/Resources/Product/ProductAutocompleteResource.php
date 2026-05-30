<?php

namespace App\Http\Resources\Product;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Product $resource
 * @mixin Product
 */
class ProductAutocompleteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstListing = $this->whenLoaded('listings', function () {
            return $this->listings->first();
        });

        $originalPrice = $firstListing?->base_price ?? 0;
        $hasDiscount   = $this->resource->hasActiveDiscount();
        $finalPrice    = $hasDiscount
            ? $this->resource->calculateDiscountedPrice($originalPrice)
            : $originalPrice;

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'main_image'       => $this->resolveImageUrl($this->main_image),
            'price'            => $finalPrice,
            'old_price'        => $hasDiscount ? $originalPrice : null,
            'discount_percent' => $hasDiscount ? $this->discount_percent : null,
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
