<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function getUserOrders(User $user, array $filters = []): LengthAwarePaginator;
    
    public function getAllOrders(array $filters = []): LengthAwarePaginator;
    
    public function findById(int $id): ?Order;
}
