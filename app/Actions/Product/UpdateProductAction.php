<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use App\Services\ProductService;

class UpdateProductAction
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function execute(ProductUpdateRequest $request, Product $product): Product
    {
        $product = $this->productService->updateProduct($product, $request->validatedData());

        return $product->load(['categories', 'images', 'relatedProducts', 'variants.optionValues']);
    }
}
