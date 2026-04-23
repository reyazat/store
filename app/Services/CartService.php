<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\ListingVariant;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CartService
{
    public function __construct(private readonly CartRepositoryInterface $cartRepository)
    {
    }

    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart
    {
        $cart = $this->cartRepository->findByUser($user, $sessionId);
        
        if (!$cart) {
            $cart = $this->cartRepository->createCart($user, $sessionId);
        }
        
        return $cart->load('items.listing.product', 'items.listing.vendor');
    }

    public function addItem(Cart $cart, int $listingId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        if ($quantity <= 0) {
            throw new RuntimeException('تعداد باید بزرگتر از صفر باشد');
        }

        $listing = Listing::where('status', 'active')->findOrFail($listingId);

        $price = $listing->base_price;
        $availableStock = null;

        if ($variantId) {
            $listingVariant = ListingVariant::where('id', $variantId)
                ->where('listing_id', $listing->id)
                ->where('status', 1)
                ->first();

            if (!$listingVariant) {
                throw new RuntimeException('نوع انتخاب شده محصول موجود نیست');
            }

            $price = $listingVariant->price_override ?? $listing->base_price;
            $availableStock = $listingVariant->stock_quantity;
        } else {
            $availableStock = (int) $listing->listingVariants()
                ->where('status', 1)
                ->sum('stock_quantity');
        }

        $existingItem = $cart->items()
            ->where('listing_id', $listingId)
            ->where('listing_variant_id', $variantId)
            ->first();

        $newQuantity = ($existingItem?->quantity ?? 0) + $quantity;

        if ($availableStock !== null && $newQuantity > $availableStock) {
            throw new RuntimeException("موجودی کافی نیست. حداکثر {$availableStock} عدد قابل خرید است");
        }

        if ($listing->min_order_qty && $newQuantity < $listing->min_order_qty) {
            throw new RuntimeException("حداقل تعداد سفارش {$listing->min_order_qty} عدد است");
        }

        if ($existingItem) {
            $existingItem->quantity = $newQuantity;
            $existingItem->price = $price;
            $existingItem->save();
            return $existingItem;
        }

        return $cart->items()->create([
            'listing_id' => $listingId,
            'listing_variant_id' => $variantId,
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }

    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $item->delete();
            return $item;
        }

        $item->loadMissing('listing', 'listingVariant');

        $availableStock = null;
        if ($item->listing_variant_id && $item->listingVariant) {
            $availableStock = $item->listingVariant->stock_quantity;
        } elseif ($item->listing) {
            $availableStock = (int) $item->listing->listingVariants()
                ->where('status', 1)
                ->sum('stock_quantity');
        }

        if ($availableStock !== null && $quantity > $availableStock) {
            throw new RuntimeException("موجودی کافی نیست. حداکثر {$availableStock} عدد قابل خرید است");
        }

        $item->update(['quantity' => $quantity]);
        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function mergeCarts(Cart $guestCart, Cart $userCart): Cart
    {
        DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $userCart->items()
                    ->where('listing_id', $guestItem->listing_id)
                    ->where('listing_variant_id', $guestItem->listing_variant_id)
                    ->first();
                
                if ($existingItem) {
                    $existingItem->quantity += $guestItem->quantity;
                    $existingItem->save();
                } else {
                    $guestItem->update(['cart_id' => $userCart->id]);
                }
            }
            
            $guestCart->delete();
        });
        
        return $userCart->fresh('items.listing.product');
    }
}
