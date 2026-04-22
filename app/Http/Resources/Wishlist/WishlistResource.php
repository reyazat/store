<?php

namespace App\Http\Resources\Wishlist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product'    => $this->whenLoaded('product', fn () => [
                'id'           => $this->product->id,
                'name'         => $this->product->name,
                'slug'         => $this->product->slug,
                'main_image'   => $this->product->main_image,
                'manufacturer' => $this->product->manufacturer?->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
