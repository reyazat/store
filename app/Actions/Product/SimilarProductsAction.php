<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class SimilarProductsAction
{
    public function execute(Product $product, int $limit = 8): Collection
    {
        $categoryIds = $product->categories->pluck('id');

        if ($categoryIds->isEmpty()) {
            return new Collection();
        }

        return Product::query()
            ->where('status', 1)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->with([
                'listings' => fn ($q) => $q->where('status', 'active')
                    ->with(['listingVariants' => fn ($v) => $v->where('status', 1)]),
                'manufacturer',
            ])
            ->orderByDesc('sorted')
            ->orderByDesc('viewed')
            ->limit($limit)
            ->get();
    }
}
