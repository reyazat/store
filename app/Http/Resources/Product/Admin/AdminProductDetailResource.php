<?php

namespace App\Http\Resources\Product\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Product $resource
 * @mixin Product
 */
class AdminProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'base_sku'         => $this->base_sku,
            'short_description' => $this->short_description,
            'description'      => $this->description,
            'main_image'       => $this->resolveImageUrl($this->main_image),
            'manufacturer_id'  => $this->manufacturer_id,
            'tax_class_id'     => $this->tax_class_id,
            'weight'           => $this->weight,
            'length'           => $this->length,
            'width'            => $this->width,
            'height'           => $this->height,
            'date_available'   => optional($this->date_available)->toDateString(),
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'sorted'           => $this->sorted,
            'status'           => $this->status,
            'discount_percent' => $this->discount_percent,
            'discount_start_at' => $this->discount_start_at?->toDateTimeString(),
            'discount_end_at'   => $this->discount_end_at?->toDateTimeString(),
            'created_at'       => $this->created_at?->toDateTimeString(),
            'updated_at'       => $this->updated_at?->toDateTimeString(),

            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])),

            'images' => $this->whenLoaded('images', fn () => $this->images->sortBy('sorted')->map(fn ($img) => [
                'id'        => $img->id,
                'file_name' => $this->resolveImageUrl($img->file_name),
                'sorted'    => $img->sorted,
            ])->values()),

            'related_products' => $this->whenLoaded('relatedProducts', fn () => $this->relatedProducts->map(fn ($r) => [
                'id'         => $r->id,
                'name'       => $r->name,
                'slug'       => $r->slug,
                'main_image' => $this->resolveImageUrl($r->main_image),
            ])->values()),

            'options' => $this->whenLoaded('options', fn () => $this->options->sortBy('sorted')->map(fn ($opt) => [
                'id'       => $opt->id,
                'name'     => $opt->name,
                'slug'     => $opt->slug,
                'type'     => $opt->type,
                'required' => (bool) ($opt->pivot->required ?? false),
                'values'   => $opt->relationLoaded('values')
                    ? $opt->values->sortBy('sorted')->map(fn ($v) => [
                        'id'     => $v->id,
                        'value'  => $v->value,
                        'sorted' => $v->sorted,
                    ])->values()
                    : [],
            ])->values()),

            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'id'      => $variant->id,
                'sku'     => $variant->sku,
                'barcode' => $variant->barcode,
                'weight'  => $variant->weight,
                'length'  => $variant->length,
                'width'   => $variant->width,
                'height'  => $variant->height,
                'status'  => $variant->status,
                'option_values' => $variant->relationLoaded('variantOptionValues')
                    ? $variant->variantOptionValues->map(fn ($vov) => [
                        'option_id'       => $vov->option_id,
                        'option_name'     => $vov->relationLoaded('option') ? $vov->option->name : null,
                        'option_value_id' => $vov->option_value_id,
                        'value'           => $vov->relationLoaded('optionValue') ? $vov->optionValue->value : null,
                    ])->values()
                    : [],
                'price'              => optional($variant->listingVariants->first())->price_override,
                'stock'              => optional($variant->listingVariants->first())->stock_quantity ?? 0,
                'listing_variant_id' => optional($variant->listingVariants->first())->id,
            ])->values()),
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
