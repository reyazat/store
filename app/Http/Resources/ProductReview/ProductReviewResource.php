<?php

namespace App\Http\Resources\ProductReview;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'rating'      => $this->rating,
            'title'       => $this->title,
            'body'        => $this->body,
            'status'      => $this->status,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'reply'       => $this->reply,
            'replied_at'  => $this->replied_at?->toDateTimeString(),
            'created_at'  => $this->created_at->toDateTimeString(),
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'product'     => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ]),
        ];
    }
}
