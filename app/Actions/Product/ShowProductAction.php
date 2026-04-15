<?php


namespace App\Actions\Product;

use App\Models\Product;

class ShowProductAction
{
    public function execute(Product $product): Product
    {
        return $product->load([
            'categories',
            'images',
            'manufacturer',
            'options.values',
            'variants' => function ($query) {
                $query->where('status', 1)
                    ->with([
                        'optionValues.option',
                        'listingVariants' => function ($q) {
                            $q->where('status', 1);
                        }
                    ]);
            },
            'listings' => function ($query) {
                $query->where('status', 'active');
            }
        ]);
    }
}
