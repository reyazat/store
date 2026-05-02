<?php

namespace App\Http\Resources\Category;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read Category $resource
 * @mixin Category
 */
class CategoryListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'parent_id'      => $this->parent_id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'image'          => $this->image,
            'icon'           => $this->icon,
            'products_count' => $this->whenCounted('products'),
            'status'         => $this->status,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
