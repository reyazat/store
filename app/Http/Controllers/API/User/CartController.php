<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $cart = $this->cartService->getOrCreateCart($user);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cart->id,
                'items' => $cart->items->map(fn($item) => [
                    'id' => $item->id,
                    'listing_id' => $item->listing_id,
                    'listing_variant_id' => $item->listing_variant_id,
                    'variant_id' => $item->listingVariant?->variant_id,
                    'product' => [
                        'id' => $item->listing->product->id,
                        'name' => $item->listing->product->name,
                        'slug' => $item->listing->product->slug,
                        'image' => $item->listing->product->main_image,
                    ],
                    'vendor' => $item->listing->vendor ? [
                        'id' => $item->listing->vendor->id,
                        'name' => $item->listing->vendor->name,
                    ] : null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ]),
                'total' => $cart->total,
                'items_count' => $cart->items_count,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_id' => 'required|integer|exists:listings,id',
            'quantity' => 'nullable|integer|min:1',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);
        
        $user = $request->user();
        
        $cart = $this->cartService->getOrCreateCart($user);
        
        // Find listing_variant_id from variant_id and listing_id
        $listingVariantId = null;
        if (isset($validated['variant_id'])) {
            $listingVariant = \App\Models\ListingVariant::where('listing_id', $validated['listing_id'])
                ->where('variant_id', $validated['variant_id'])
                ->first();
            $listingVariantId = $listingVariant?->id;
        }
        
        $item = $this->cartService->addItem(
            $cart,
            $validated['listing_id'],
            $validated['quantity'] ?? 1,
            $listingVariantId
        );
        
        return response()->json([
            'success' => true,
            'message' => 'محصول به سبد خرید اضافه شد',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);
        
        $item = $this->cartService->updateItemQuantity($cartItem, $validated['quantity']);
        
        return response()->json([
            'success' => true,
            'message' => 'سبد خرید به‌روزرسانی شد',
            'data' => $item,
        ]);
    }

    public function destroy(CartItem $cartItem): JsonResponse
    {
        $this->cartService->removeItem($cartItem);
        
        return response()->json([
            'success' => true,
            'message' => 'محصول از سبد خرید حذف شد',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $cart = $this->cartService->getOrCreateCart($user);
        $this->cartService->clearCart($cart);
        
        return response()->json([
            'success' => true,
            'message' => 'سبد خرید خالی شد',
        ]);
    }
}
