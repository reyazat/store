<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\WishlistStoreRequest;
use App\Http\Resources\Wishlist\WishlistResource;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = $request->user()
            ->wishlists()
            ->with('product.manufacturer')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => WishlistResource::collection($wishlists),
        ]);
    }

    public function store(WishlistStoreRequest $request): JsonResponse
    {
        $productId = $request->validated('product_id');

        $existing = $request->user()
            ->wishlists()
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'این محصول قبلاً به لیست علاقه‌مندی‌ها اضافه شده است',
            ], 400);
        }

        $wishlist = $request->user()->wishlists()->create(['product_id' => $productId]);

        return response()->json([
            'success' => true,
            'message' => 'محصول به لیست علاقه‌مندی‌ها اضافه شد',
            'data'    => new WishlistResource($wishlist),
        ], 201);
    }

    public function destroy(Request $request, Wishlist $wishlist): JsonResponse
    {
        if ($wishlist->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'محصول از لیست علاقه‌مندی‌ها حذف شد',
        ]);
    }
}
