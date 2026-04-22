<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'listing_id'          => $this->listing_id,
            'listing_variant_id'  => $this->listing_variant_id,
            'variant_id'          => $this->listingVariant?->variant_id,
            'product'             => $this->whenLoaded('listing', fn () => [
                'id'    => $this->listing->product->id,
                'name'  => $this->listing->product->name,
                'slug'  => $this->listing->product->slug,
                'image' => $this->listing->product->main_image,
            ]),
            'vendor'              => $this->whenLoaded('listing', fn () => $this->listing->vendor ? [
                'id'   => $this->listing->vendor->id,
                'name' => $this->listing->vendor->name,
            ] : null),
            'quantity'            => $this->quantity,
            'price'               => $this->price,
            'subtotal'            => $this->subtotal,
        ];
    }
}
