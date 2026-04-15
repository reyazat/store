<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Actions\Product\ShowProductAction;
use App\Actions\Product\SearchProductAction;
use App\Actions\Product\StoreProductAction;
use App\Actions\Product\UpdateProductAction;
use App\Actions\Product\DestroyProductAction;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductDetailResource;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Product\ProductSearchRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly SearchProductAction $searchAction,
        private readonly ShowProductAction $showAction,
        private readonly StoreProductAction $storeAction,
        private readonly UpdateProductAction $updateAction,
        private readonly DestroyProductAction $destroyAction
    ) {
    }

    public function index(ProductSearchRequest $request): JsonResponse
    {
        $products = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => ProductListResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        $product = $this->showAction->execute($product);

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
        $product = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ], 201);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $product = $this->updateAction->execute($request, $product);

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->destroyAction->execute($product);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}
