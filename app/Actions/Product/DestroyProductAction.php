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
        $this->productService->deleteProduct($product);
    }
}
