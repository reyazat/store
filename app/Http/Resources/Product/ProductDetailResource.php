<?php

namespace App\Http\Resources\Product;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read Product $resource
 * @mixin Product
 */
class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'base_sku' => $this->base_sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'main_image' => $this->main_image,
            'manufacturer_id' => $this->manufacturer_id,
            'manufacturer' => $this->whenLoaded('manufacturer', fn () => [
                'id' => $this->manufacturer->id,
                'name' => $this->manufacturer->name,
                'slug' => $this->manufacturer->slug,
            ]),
            'tax_class_id' => $this->tax_class_id,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'date_available' => optional($this->date_available)->toDateString(),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'viewed' => $this->viewed,
            'sorted' => $this->sorted,
            'status' => $this->status,
            'discount_percent' => $this->discount_percent,
            'discount_start_at' => $this->discount_start_at?->toDateTimeString(),
            'discount_end_at' => $this->discount_end_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])),
            'images' => $this->whenLoaded('images', fn () => $this->images->sortBy('sorted')->map(fn ($image) => [
                'id' => $image->id,
                'file_name' => $image->file_name,
                'sorted' => $image->sorted,
            ])->values()),
            'options' => $this->whenLoaded('options', fn () => $this->options->sortBy('sorted')->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'slug' => $option->slug,
                'type' => $option->type,
                'required' => $option->pivot->required ?? false,
                'values' => $option->relationLoaded('values')
                    ? $option->values->sortBy('sorted')->map(fn ($value) => [
                        'id' => $value->id,
                        'value' => $value->value,
                        'sorted' => $value->sorted,
                    ])->values()
                    : [],
            ])->values()),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->where('status', 1)->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'weight' => $variant->weight,
                'length' => $variant->length,
                'width' => $variant->width,
                'height' => $variant->height,
                'status' => $variant->status,
                'option_values' => $variant->relationLoaded('optionValues')
                    ? $variant->optionValues->map(fn ($optionValue) => [
                        'id' => $optionValue->id,
                        'option_id' => $optionValue->option_id,
                        'value' => $optionValue->value,
                        'option_name' => $optionValue->relationLoaded('option') ? $optionValue->option->slug : null,
                    ])
                    : [],
                'listing_variant' => $variant->relationLoaded('listingVariants')
                    ? optional($variant->listingVariants->where('status', 1)->first())->only(['id', 'price_override', 'stock_quantity', 'listing_id'])
                    : null,
            ])->values()),
            'listings' => $this->whenLoaded('listings', fn () => $this->listings->where('status', 'active')->map(fn ($listing) => [
                'id' => $listing->id,
                'vendor_id' => $listing->vendor_id,
                'base_price' => $listing->base_price,
                'min_order_qty' => $listing->min_order_qty,
                'status' => $listing->status,
            ])->values()),
        ];
    }
}
