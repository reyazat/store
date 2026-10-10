<?php

namespace App\Http\Controllers\API\Store;

use App\Actions\Product\Admin\ShowAdminProductAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Option\OptionDetailResource;
use App\Http\Resources\Product\Admin\AdminProductDetailResource;
use App\Http\Resources\Product\ProductListResource;
use App\Models\Option;
use App\Models\Product;
use App\Services\ProductService;
use IamApi\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ShowAdminProductAction $showAdminAction,
    ) {}

    /**
     * List all products belonging to the authenticated store owner's organization.
     * Store owners can only see their own products (all statuses visible for management).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $filters = $request->only(['search', 'per_page', 'page']);

        // Always scope to the authenticated store owner's organization
        $filters['organization_id'] = $user->organization_id;

        // Store management: allow explicit status filter; default to null (all statuses)
        if ($request->has('status') && $request->input('status') !== '') {
            $filters['status'] = (int) $request->input('status');
        } else {
            $filters['status'] = null;
        }

        $products = $this->productService->getProducts($filters);

        // Load relations needed by ProductListResource
        $products->load([
            'manufacturer',
            'listings' => function ($query) {
                $query->where('status', 'active')
                    ->with(['listingVariants' => function ($q) {
                        $q->where('status', 1);
                    }]);
            },
        ]);

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

    public function availableOptions(): JsonResponse
    {
        $options = Option::query()
            ->with('values')
            ->orderBy('sorted')
            ->get();

        return response()->json([
            'success' => true,
            'data' => OptionDetailResource::collection($options),
        ]);
    }

    /**
     * Return full product detail for a store owner's product (used by the edit form).
     *
     * This endpoint mirrors GET /admin/products/{product} but is accessible to store
     * members (not blocked by admin middleware).  Ownership is enforced: only the
     * product's own organization may fetch it here.
     */
    public function show(Product $product): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($product->organization_id !== $user->organization_id) {
            return response()->json([
                'success' => false,
                'message' => 'این محصول متعلق به فروشگاه شما نیست.',
            ], 403);
        }

        $product = $this->showAdminAction->execute($product);

        return response()->json([
            'success' => true,
            'data' => new AdminProductDetailResource($product),
        ]);
    }
}
