<?php

namespace App\Http\Resources\Category;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
/**
 * @property-read Category $resource
 * @mixin Category
 */
class CategoryListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'parent_id'           => $this->parent_id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'image'               => $this->resolveImageUrl($this->image),
            'icon'                => $this->icon,
            'products_count'      => $this->when(isset($this->total_products_count), $this->total_products_count, $this->whenCounted('products')),
            'direct_products_count' => $this->when(isset($this->direct_products_count), $this->direct_products_count, 0),
            'children_count'      => $this->whenCounted('children', $this->children_count, 0),
            'status'              => $this->status,
            'sorted'              => $this->sorted ?? 0,
            'created_at'          => $this->created_at?->toDateTimeString(),
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
