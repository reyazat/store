<?php

namespace App\Repositories\Eloquents;

use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function getUserOrders(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Order::where('user_id', $user->id)->with(['items', 'orderAddress']);
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->latest()->paginate($perPage);
    }
    
    public function getAllOrders(array $filters = []): LengthAwarePaginator
    {
        $query = Order::with(['user', 'items', 'orderAddress']);
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['search'])) {
            $query->where('order_number', 'like', '%' . $filters['search'] . '%');
        }
        
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->latest()->paginate($perPage);
    }
    
    public function findById(int $id): ?Order
    {
        return Order::with(['user', 'items.product', 'items.vendor', 'orderAddress'])->find($id);
    }
}
