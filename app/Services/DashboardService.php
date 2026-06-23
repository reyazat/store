<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use IamApi\Models\Organization;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats(): array
    {
        $paidOrderQuery = Order::where('payment_status', 'paid');
        $paidOrdersCount = (clone $paidOrderQuery)->count();
        $totalGmv = (clone $paidOrderQuery)->sum('total');

        return [
            'total_orders'        => Order::count(),
            'total_revenue'       => $totalGmv,
            'total_users'         => User::where('is_super_admin', false)->count(),
            'total_products'      => Product::count(),
            'total_stores'        => Organization::count(),
            'active_stores'       => Organization::where('status', 'active')->count(),
            'inactive_stores'     => Organization::where('status', 'inactive')->count(),
            'suspended_stores'    => Organization::where('status', 'suspended')->count(),
            'total_sellers'       => User::where('is_super_admin', false)->whereNotNull('organization_id')->count(),
            'total_buyers'        => User::where('is_super_admin', false)->whereNull('organization_id')->count(),
            'total_listings'      => Listing::count(),
            'pending_reviews'     => ProductReview::where('status', 'pending')->count(),
            'pending_orders'      => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders'      => Order::where('status', 'shipped')->count(),
            'orders_today'        => Order::whereDate('created_at', today())->count(),
            'revenue_today'       => Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total'),
            'aov'                 => $paidOrdersCount > 0 ? round($totalGmv / $paidOrdersCount, 2) : 0,
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

    public function getMonthlyRevenue(int $months = 12): array
    {
        return Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => [
                'month'   => $row->month,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    public function getOrderStatusBreakdown(): array
    {
        return Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn($row) => [
                'status' => $row->status,
                'count'  => (int) $row->count,
            ])
            ->all();
    }

    public function getTopStores(int $limit = 6): array
    {
        $statusLabels = [
            'active'    => 'فعال',
            'inactive'  => 'غیرفعال',
            'suspended' => 'معلق',
        ];

        return Organization::query()
            ->select('organizations.*', DB::raw('COALESCE(SUM(paid_orders.total), 0) as gmv'))
            ->leftJoin('products', 'products.organization_id', '=', 'organizations.id')
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin(
                DB::raw('(SELECT id, total FROM orders WHERE payment_status = "paid") as paid_orders'),
                'paid_orders.id',
                '=',
                'order_items.order_id'
            )
            ->groupBy('organizations.id')
            ->orderByDesc('gmv')
            ->limit($limit)
            ->get()
            ->map(fn($store) => [
                'id'          => $store->id,
                'uuid'        => $store->uuid,
                'name'        => $store->name,
                'status'      => $store->status,
                'status_label'=> $statusLabels[$store->status] ?? $store->status,
                'gmv'         => (float) $store->gmv,
                'products_count' => $store->products()->count(),
            ])
            ->all();
    }

    public function getPendingReviews(int $limit = 5): array
    {
        return ProductReview::with(['product', 'user'])
            ->where('status', 'pending')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($review) => [
                'id'           => $review->id,
                'rating'       => $review->rating,
                'title'        => $review->title,
                'body'         => $review->body,
                'product_name' => $review->product?->name,
                'customer_name'=> $review->user?->name,
                'created_at'   => $review->created_at,
            ])
            ->all();
    }

    public function getStoreGrowth(int $months = 12): array
    {
        return Organization::where('created_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($row) => [
                'month' => $row->month,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    public function getUserGrowth(int $months = 12): array
    {
        $buyers = User::where('is_super_admin', false)
            ->whereNull('organization_id')
            ->where('created_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($row) => [$row->month => (int) $row->count]);

        $sellers = User::where('is_super_admin', false)
            ->whereNotNull('organization_id')
            ->where('created_at', '>=', now()->subMonths($months))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($row) => [$row->month => (int) $row->count]);

        $monthsList = collect(range($months - 1, 0))
            ->map(fn($i) => now()->subMonths($i)->format('Y-m'))
            ->unique()
            ->sort()
            ->values();

        return $monthsList->map(fn($month) => [
            'month'  => $month,
            'buyers' => $buyers[$month] ?? 0,
            'sellers'=> $sellers[$month] ?? 0,
        ])->all();
    }
}
