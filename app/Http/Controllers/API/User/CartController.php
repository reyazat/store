<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\CartAddItemRequest;
use App\Http\Requests\Cart\CartUpdateItemRequest;
use App\Http\Resources\Cart\CartItemResource;
use App\Http\Resources\Cart\CartResource;
use App\Models\CartItem;
use App\Models\ListingVariant;
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
        $cart = $this->cartService->getOrCreateCart($request->user());

        return response()->json([
            'success' => true,
            'data'    => new CartResource($cart),
        ]);
    }

    public function store(CartAddItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cart = $this->cartService->getOrCreateCart($request->user());

        $listingVariantId = null;
        if (isset($validated['variant_id'])) {
            $listingVariant = ListingVariant::where('listing_id', $validated['listing_id'])
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
            'data'    => new CartItemResource($item),
        ], 201);
    }

    public function update(CartUpdateItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $item = $this->cartService->updateItemQuantity($cartItem, $request->validated('quantity'));

        return response()->json([
            'success' => true,
            'message' => 'سبد خرید به‌روزرسانی شد',
            'data'    => new CartItemResource($item),
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
        $cart = $this->cartService->getOrCreateCart($request->user());
        $this->cartService->clearCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'سبد خرید خالی شد',
        ]);
    }
}
