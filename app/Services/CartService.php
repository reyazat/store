<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;

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
        $listing = Listing::findOrFail($listingId);
        
        $existingItem = $cart->items()
            ->where('listing_id', $listingId)
            ->where('listing_variant_id', $variantId)
            ->first();
        
        if ($existingItem) {
            $existingItem->quantity += $quantity;
            $existingItem->save();
            return $existingItem;
        }
        
        // Get price from listing_variant if exists, otherwise use base_price
        $price = $listing->base_price;
        if ($variantId) {
            $listingVariant = $listing->listingVariants()
                ->where('id', $variantId)
                ->first();
            if ($listingVariant) {
                $price = $listingVariant->price_override ?? $listing->base_price;
            }
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
