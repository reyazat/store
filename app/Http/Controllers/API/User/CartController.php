<?php

namespace App\Http\Controllers\API\User;

use App\Actions\Coupon\ApplyCouponAction;
use App\Actions\Coupon\RemoveCouponAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\CartAddItemRequest;
use App\Http\Requests\Cart\CartUpdateItemRequest;
use App\Http\Resources\Cart\CartItemResource;
use App\Http\Resources\Cart\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ListingVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService         $cartService,
        private readonly ApplyCouponAction   $applyCouponAction,
        private readonly RemoveCouponAction  $removeCouponAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $cart->load('coupon');

        return response()->json([
            'success' => true,
            'data'    => new CartResource($cart),
        ]);
    }

    public function store(CartAddItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cart = $this->cartService->getOrCreateCart($request->user());

        // variant_id is now the listing_variant.id (direct reference)
        $listingVariantId = $validated['variant_id'] ?? null;

        // Validate that the listing variant belongs to the specified listing
        if ($listingVariantId) {
            $listingVariant = ListingVariant::where('id', $listingVariantId)
                ->where('listing_id', $validated['listing_id'])
                ->where('status', 1)
                ->first();

            if (!$listingVariant) {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع انتخاب شده محصول موجود نیست',
                ], 422);
            }
        }

        try {
            $item = $this->cartService->addItem(
                $cart,
                $validated['listing_id'],
                $validated['quantity'] ?? 1,
                $listingVariantId
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'محصول به سبد خرید اضافه شد',
            'data'    => new CartItemResource($item),
        ], 201);
    }

    public function update(CartUpdateItemRequest $request, CartItem $cartItem): JsonResponse
    {
        // Verify ownership - cart item must belong to current user's cart
        if (!$this->cartItemBelongsToUser($cartItem, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه دسترسی به این آیتم سبد خرید را ندارید',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $item = $this->cartService->updateItemQuantity($cartItem, $request->validated('quantity'));
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'سبد خرید به‌روزرسانی شد',
            'data'    => new CartItemResource($item),
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        // Verify ownership - cart item must belong to current user's cart
        if (!$this->cartItemBelongsToUser($cartItem, $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه دسترسی به این آیتم سبد خرید را ندارید',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->cartService->removeItem($cartItem);

        return response()->json([
            'success' => true,
            'message' => 'محصول از سبد خرید حذف شد',
        ]);
    }

    /**
     * Verify that the cart item belongs to the given user.
     */
    private function cartItemBelongsToUser(CartItem $cartItem, ?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        if (!$user) {
            return false;
        }

        // Load cart relationship if not already loaded
        if (!$cartItem->relationLoaded('cart')) {
            $cartItem->load('cart');
        }

        return $cartItem->cart && $cartItem->cart->user_id === $user->getKey();
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

    // ─── Coupon endpoints ─────────────────────────────────────────────────────

    /**
     * Apply a coupon code to the current user's cart.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $cart = $this->cartService->getOrCreateCart($user);
        $cart->loadMissing(['items.listing.product']);

        if ($cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'سبد خرید خالی است.',
            ], 422);
        }

        $result = $this->applyCouponAction->execute($cart, $user, $data['code']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        $cart->refresh()->load(['items.listing.product', 'items.listing.vendor', 'items.listingVariant', 'coupon']);

        return response()->json([
            'success'  => true,
            'message'  => $result['message'],
            'data'     => new CartResource($cart),
        ]);
    }

    /**
     * Remove the applied coupon from the current user's cart.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $this->removeCouponAction->execute($cart);
        $cart->refresh()->load(['items.listing.product', 'items.listing.vendor', 'items.listingVariant', 'coupon']);

        return response()->json([
            'success' => true,
            'message' => 'کوپن حذف شد.',
            'data'    => new CartResource($cart),
        ]);
    }
}
