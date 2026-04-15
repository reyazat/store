<?php


namespace App\Actions\Product;

use App\Services\ProductService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Http\Requests\Product\ProductSearchRequest;


class SearchProductAction
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function execute(ProductSearchRequest $request): LengthAwarePaginator
    {
        $filters = $request->validatedFilters();
        $products = $this->productService->getProducts($filters);
        
        // Load necessary relations for ProductListResource
        $products->load([
            'manufacturer',
            'listings' => function ($query) {
                $query->where('status', 'active')
                    ->with(['listingVariants' => function ($q) {
                        $q->where('status', 1);
                    }]);
            }
        ]);
        
        return $products;
    }
}
