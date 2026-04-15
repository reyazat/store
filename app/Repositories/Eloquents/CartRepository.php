<?php

namespace App\Repositories\Eloquents;

use App\Models\Cart;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;

class CartRepository implements CartRepositoryInterface
{
    public function findByUser(?User $user, ?string $sessionId = null): ?Cart
    {
        if ($user) {
            return Cart::where('user_id', $user->id)->with('items.listing.product')->first();
        }
        
        if ($sessionId) {
            return Cart::where('session_id', $sessionId)->with('items.listing.product')->first();
        }
        
        return null;
    }
    
    public function createCart(?User $user, ?string $sessionId = null): Cart
    {
        return Cart::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
        ]);
    }
}
