<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats(): array
    {
        return [
            'total_orders'      => Order::count(),
            'total_revenue'     => Order::where('payment_status', 'paid')->sum('total'),
            'total_users'       => User::whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))->count(),
            'total_products'    => Product::count(),
            'pending_orders'    => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders'    => Order::where('status', 'shipped')->count(),
            'orders_today'      => Order::whereDate('created_at', today())->count(),
            'revenue_today'     => Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total'),
        ];
    }

    public function getRecentOrders(int $limit = 10): array
    {
        $statusLabels = [
            'pending'    => 'در انتظار',
            'processing' => 'در حال پردازش',
            'shipped'    => 'ارسال شده',
            'delivered'  => 'تحویل شده',
            'cancelled'  => 'لغو شده',
            'refunded'   => 'مسترد شده',
        ];

        return Order::with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($order) => [
                'id'            => $order->id,
                'order_number'  => $order->order_number,
                'customer_name' => $order->user?->name,
                'total'         => $order->total,
                'status'        => $order->status,
                'status_label'  => $statusLabels[$order->status] ?? $order->status,
                'created_at'    => $order->created_at,
            ])
            ->all();
    }

    public function getTopProducts(int $limit = 10): array
    {
        return Product::withCount(['orderItems' => fn($q) => $q->whereHas(
            'order', fn($query) => $query->where('payment_status', 'paid')
        )])
            ->orderByDesc('order_items_count')
            ->limit($limit)
            ->get()
            ->map(fn($product) => [
                'id'          => $product->id,
                'name'        => $product->name,
                'sales_count' => $product->order_items_count,
            ])
            ->all();
    }

    public function getMonthlyRevenue(int $months = 12): mixed
    {
        return Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
