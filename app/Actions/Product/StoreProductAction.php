<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductStoreRequest;
use App\Models\Product;
use App\Services\ProductService;
use Throwable;

class StoreProductAction
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(ProductStoreRequest $request): Product
    {
        return $this->productService->createProduct($request->validatedData())->load(['categories', 'images', 'variants.optionValues']);
    }
}
