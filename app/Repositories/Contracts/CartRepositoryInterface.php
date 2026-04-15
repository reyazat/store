<?php

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\User;

interface CartRepositoryInterface
{
    public function findByUser(?User $user, ?string $sessionId = null): ?Cart;
    
    public function createCart(?User $user, ?string $sessionId = null): Cart;
}
