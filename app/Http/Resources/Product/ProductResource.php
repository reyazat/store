<?php

namespace App\Http\Resources\Product;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read Product $resource
 * @mixin Product
 */
class ProductResource extends JsonResource
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
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'file_name' => $image->file_name,
                'sorted' => $image->sorted,
            ])),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'weight' => $variant->weight,
                'length' => $variant->length,
                'width' => $variant->width,
                'height' => $variant->height,
                'status' => $variant->status,
            ])),
        ];
    }
}
