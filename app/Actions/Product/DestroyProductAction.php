<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Services\ProductService;

class DestroyProductAction
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function execute(Product $product): void
    {
        // Authorization is handled by ProductPolicy
        // Controller should call $this->authorize('delete', $product) before calling this action
        
        $this->productService->deleteProduct($product);
    }
}
