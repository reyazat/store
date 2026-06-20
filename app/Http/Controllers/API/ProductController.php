<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Actions\Product\ShowProductAction;
use App\Actions\Product\SearchProductAction;
use App\Actions\Product\SimilarProductsAction;
use App\Actions\Product\ProductAutocompleteAction;
use App\Actions\Product\StoreProductAction;
use App\Actions\Product\UpdateProductAction;
use App\Actions\Product\DestroyProductAction;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductDetailResource;
use App\Http\Resources\Product\ProductAutocompleteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Product\ProductSearchRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly SearchProductAction       $searchAction,
        private readonly ShowProductAction         $showAction,
        private readonly SimilarProductsAction     $similarAction,
        private readonly ProductAutocompleteAction $autocompleteAction,
        private readonly StoreProductAction        $storeAction,
        private readonly UpdateProductAction       $updateAction,
        private readonly DestroyProductAction      $destroyAction,
    ) {}

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

    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 3) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $products = $this->autocompleteAction->execute($query);

        return response()->json([
            'success' => true,
            'data'    => ProductAutocompleteResource::collection($products),
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

    public function similar(Product $product): JsonResponse
    {
        $result = $this->similarAction->execute($product);

        return response()->json([
            'success' => true,
            'source'  => $result['source'],
            'data'    => ProductListResource::collection($result['items']),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
        // Authorize: only store members can create products
        $this->authorize('create', Product::class);

        $product = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ], 201);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        // Authorize: user must own the product (same organization)
        $this->authorize('update', $product);

        $product = $this->updateAction->execute($request, $product);

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        // Authorize: user must own the product (same organization)
        $this->authorize('delete', $product);

        $this->destroyAction->execute($product);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}
