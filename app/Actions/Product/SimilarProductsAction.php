<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class SimilarProductsAction
{
    public function execute(Product $product, int $limit = 8): array
    {
        $product->loadMissing('relatedProducts');

        if ($product->relatedProducts->isNotEmpty()) {
            $items = $product->relatedProducts
                ->where('status', 1)
                ->take($limit)
                ->values();

            $this->loadListingsAndManufacturer($items);

            return ['source' => 'manual', 'items' => $items];
        }

        $product->loadMissing('categories');
        $categoryIds = $product->categories->pluck('id');

        if ($categoryIds->isEmpty()) {
            return ['source' => 'algorithm', 'items' => new Collection()];
        }

        $items = Product::query()
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

        return ['source' => 'algorithm', 'items' => $items];
    }

    private function loadListingsAndManufacturer(Collection $products): void
    {
        $products->loadMissing([
            'listings' => fn ($q) => $q->where('status', 'active')
                ->with(['listingVariants' => fn ($v) => $v->where('status', 1)]),
            'manufacturer',
        ]);
    }
}
