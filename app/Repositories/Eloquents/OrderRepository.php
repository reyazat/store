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
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $search . '%'));
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
            $query->where('total', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
            $query->where('total', '<=', $filters['max_amount']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->latest()->paginate($perPage);
    }
    
    public function findById(int $id): ?Order
    {
        return Order::with(['user', 'items.product', 'items.vendor', 'orderAddress'])->find($id);
    }
}
