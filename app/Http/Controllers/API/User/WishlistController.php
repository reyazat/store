<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = $request->user()
            ->wishlists()
            ->with('product.categories', 'product.manufacturer')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $wishlists->map(fn($w) => [
                'id' => $w->id,
                'product' => [
                    'id' => $w->product->id,
                    'name' => $w->product->name,
                    'slug' => $w->product->slug,
                    'main_image' => $w->product->main_image,
                    'manufacturer' => $w->product->manufacturer?->name,
                ],
                'created_at' => $w->created_at,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);
        
        $existing = $request->user()
            ->wishlists()
            ->where('product_id', $validated['product_id'])
            ->first();
        
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'این محصول قبلاً به لیست علاقه‌مندی‌ها اضافه شده است',
            ], 400);
        }
        
        $wishlist = $request->user()->wishlists()->create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'محصول به لیست علاقه‌مندی‌ها اضافه شد',
            'data' => $wishlist,
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
