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
        $data = $request->validatedData();
        
        // Auto-inject organization_id from authenticated user (store owner).
        // Products always belong to the store owner's organization.
        $data['organization_id'] = auth()->user()->organization_id;
        
        return $this->productService->createProduct($data)
            ->load(['categories', 'images', 'relatedProducts', 'variants.optionValues']);
    }
}
