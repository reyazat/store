<?php

namespace App\Actions\Product\Admin;

use App\Models\Product;

class ShowAdminProductAction
{
    public function execute(Product $product): Product
    {
        return $product->load([
            'categories',
            'images',
            'manufacturer',
            'relatedProducts',
            'options.values',
            'variants' => function ($query) {
                $query->orderBy('id')
                    ->with([
                        'variantOptionValues.option',
                        'variantOptionValues.optionValue',
                        'listingVariants' => function ($q) {
                            $q->orderBy('id')->limit(1);
                        },
                    ]);
            },
            'listings' => function ($query) {
                $query->orderBy('id')->limit(1)
                    ->with(['listingVariants' => function ($q) {
                        $q->where('status', 1);
                    }]);
            },
        ]);
    }
}
