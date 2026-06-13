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

        $dateFrom = $filters['date_from'] ?? null;
        $dateTo   = $filters['date_to']   ?? null;

        // Bug 4 fix: only apply date filters when values are valid dates
        if (!empty($dateFrom) && strtotime($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        // Bug 5 fix: enforce date_from <= date_to; skip date_to if it's before date_from
        if (!empty($dateTo) && strtotime($dateTo)) {
            if (empty($dateFrom) || $dateTo >= $dateFrom) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
        }

        // Bug 4 fix: only apply amount filters when values are valid positive numbers
        $minAmount = $filters['min_amount'] ?? null;
        $maxAmount = $filters['max_amount'] ?? null;

        if ($minAmount !== null && $minAmount !== '' && is_numeric($minAmount) && (float) $minAmount >= 0) {
            $query->where('total', '>=', (float) $minAmount);
        }

        if ($maxAmount !== null && $maxAmount !== '' && is_numeric($maxAmount) && (float) $maxAmount >= 0) {
            $query->where('total', '<=', (float) $maxAmount);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->latest()->paginate($perPage);
    }
    
    public function findById(int $id): ?Order
    {
        return Order::with(['user', 'items.product', 'items.vendor', 'orderAddress'])->find($id);
    }
}
