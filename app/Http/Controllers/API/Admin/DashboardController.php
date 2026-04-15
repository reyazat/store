<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $statusLabels = [
            'pending'    => 'در انتظار',
            'processing' => 'در حال پردازش',
            'shipped'    => 'ارسال شده',
            'delivered'  => 'تحویل شده',
            'cancelled'  => 'لغو شده',
            'refunded'   => 'مسترد شده',
        ];

        $totalOrders = Order::count();
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
        $totalUsers = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->count();
        $totalProducts = Product::count();

        $pendingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $shippedOrders = Order::where('status', 'shipped')->count();

        $ordersToday = Order::whereDate('created_at', today())->count();
        $revenueToday = Order::where('payment_status', 'paid')
            ->whereDate('created_at', today())
            ->sum('total');

        $recentOrders = Order::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->user?->name,
                'total' => $order->total,
                'status' => $order->status,
                'status_label' => $statusLabels[$order->status] ?? $order->status,
                'created_at' => $order->created_at,
            ]);
        
        $topProducts = Product::withCount(['orderItems' => function ($q) {
            $q->whereHas('order', fn($query) => $query->where('payment_status', 'paid'));
        }])
            ->orderByDesc('order_items_count')
            ->limit(10)
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sales_count' => $product->order_items_count,
            ]);
        
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_orders' => $totalOrders,
                    'total_revenue' => $totalRevenue,
                    'total_users' => $totalUsers,
                    'total_products' => $totalProducts,
                    'pending_orders' => $pendingOrders,
                    'processing_orders' => $processingOrders,
                    'shipped_orders' => $shippedOrders,
                    'orders_today' => $ordersToday,
                    'revenue_today' => $revenueToday,
                ],
                'recent_orders' => $recentOrders,
                'top_products' => $topProducts,
                'monthly_revenue' => $monthlyRevenue,
            ],
        ]);
    }
}
