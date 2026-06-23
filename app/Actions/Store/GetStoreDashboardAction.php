<?php

namespace App\Actions\Store;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use IamApi\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Assemble all data needed to render the store owner dashboard.
 *
 * Revenue is scoped per-store by joining through order_items → products.organization_id.
 * This is the correct approach for a multi-vendor marketplace where one order may
 * contain products from several stores.
 */
class GetStoreDashboardAction
{
    public function execute(int $orgId): array
    {
        $org = Organization::withCount('users')->findOrFail($orgId);

        return [
            'store'                  => $this->storeInfo($org),
            'team'                   => $this->teamBreakdown($org),
            'stats'                  => $this->stats($orgId),
            'revenue_chart'          => $this->revenueChart($orgId),
            'order_status_breakdown' => $this->orderStatusBreakdown($orgId),
            'top_products'           => $this->topProducts($orgId),
            'recent_orders'          => $this->recentOrders($orgId),
        ];
    }

    // ─── Store & team ─────────────────────────────────────────────────────────

    private function storeInfo(Organization $org): array
    {
        return [
            'id'            => $org->id,
            'uuid'          => $org->uuid,
            'name'          => $org->name,
            'description'   => $org->description,
            'status'        => $org->status,
            'members_count' => $org->users_count,
        ];
    }

    private function teamBreakdown(Organization $org): array
    {
        $byRole = $org->users->groupBy(fn($u) => $u->pivot->role ?? 'member');

        return [
            'owners'  => $byRole->get('owner',  collect())->count(),
            'admins'  => $byRole->get('admin',  collect())->count(),
            'editors' => $byRole->get('editor', collect())->count(),
            'users'   => $byRole->get('user',   collect())->count(),
            'members' => $byRole->get('member', collect())->count(),
        ];
    }

    // ─── Core KPI stats ───────────────────────────────────────────────────────

    private function stats(int $orgId): array
    {
        // Product counts
        $totalProducts  = Product::where('organization_id', $orgId)->count();
        $activeProducts = Product::where('organization_id', $orgId)->where('status', 1)->count();

        // Order counts — scoped to orders that contain at least one product from this store
        $baseOrders = fn() => Order::whereHas('items', function ($q) use ($orgId) {
            $q->whereHas('product', fn($p) => $p->where('organization_id', $orgId));
        });

        $totalOrders    = $baseOrders()->count();
        $pendingOrders  = $baseOrders()->where('status', 'pending')->count();
        $processingOrders = $baseOrders()->where('status', 'processing')->count();
        $todayOrders    = $baseOrders()->whereDate('created_at', today())->count();

        // Revenue — summed over order_items scoped to this store (correct marketplace logic)
        $totalRevenue      = $this->storeRevenue($orgId);
        $revenueThisMonth  = $this->storeRevenue($orgId, now()->startOfMonth());
        $revenueToday      = $this->storeRevenue($orgId, today());

        // Average order value for this store (revenue / total orders)
        $aov = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        // Pending reviews
        $pendingReviews = ProductReview::whereHas('product', fn($q) => $q->where('organization_id', $orgId))
            ->where('status', 'pending')
            ->count();

        return [
            'total_products'     => $totalProducts,
            'active_products'    => $activeProducts,
            'total_orders'       => $totalOrders,
            'pending_orders'     => $pendingOrders,
            'processing_orders'  => $processingOrders,
            'today_orders'       => $todayOrders,
            'total_revenue'      => round((float) $totalRevenue, 2),
            'revenue_this_month' => round((float) $revenueThisMonth, 2),
            'revenue_today'      => round((float) $revenueToday, 2),
            'avg_order_value'    => $aov,
            'pending_reviews'    => $pendingReviews,
        ];
    }

    /**
     * Sum order_items.subtotal for this store, optionally filtered from a start date.
     * Counts only paid orders.
     */
    private function storeRevenue(int $orgId, $from = null): float
    {
        $query = OrderItem::whereHas('product', fn($q) => $q->where('organization_id', $orgId))
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'));

        if ($from) {
            $query->whereHas('order', fn($q) => $q->where('created_at', '>=', $from));
        }

        return (float) ($query->sum('subtotal') ?? 0);
    }

    // ─── Revenue chart (last 7 days) ─────────────────────────────────────────

    private function revenueChart(int $orgId): array
    {
        // Raw JOIN is more efficient than nested whereHas for a GROUP BY chart query.
        // Both orders and products use SoftDeletes — filter deleted_at explicitly.
        $rows = DB::table('order_items')
            ->join('orders', function ($join) {
                $join->on('orders.id', '=', 'order_items.order_id')
                     ->whereNull('orders.deleted_at');
            })
            ->join('products', function ($join) {
                $join->on('products.id', '=', 'order_items.product_id')
                     ->whereNull('products.deleted_at');
            })
            ->where('products.organization_id', $orgId)
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(order_items.subtotal) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date    = now()->subDays($i)->format('Y-m-d');
            $label   = now()->subDays($i)->locale('fa')->isoFormat('D MMM');
            $chart[] = [
                'date'    => $date,
                'label'   => $label,
                'revenue' => round((float) ($rows->get($date)?->revenue ?? 0), 2),
            ];
        }

        return $chart;
    }

    // ─── Order status breakdown ───────────────────────────────────────────────

    private function orderStatusBreakdown(int $orgId): array
    {
        $base = fn($status) => Order::whereHas('items', function ($q) use ($orgId) {
            $q->whereHas('product', fn($p) => $p->where('organization_id', $orgId));
        })->where('status', $status)->count();

        return [
            'pending'    => $base('pending'),
            'processing' => $base('processing'),
            'shipped'    => $base('shipped'),
            'delivered'  => $base('delivered'),
            'cancelled'  => $base('cancelled'),
        ];
    }

    // ─── Top products by sales ────────────────────────────────────────────────

    private function topProducts(int $orgId): array
    {
        $products = Product::where('organization_id', $orgId)
            ->withCount(['orderItems as sales_count'])
            ->orderByDesc('sales_count')
            ->limit(5)
            ->get();

        // Load per-store revenue for each product (N+5 for a dashboard is acceptable)
        $products->each(function (Product $product) {
            $product->revenue = (float) OrderItem::where('product_id', $product->id)
                ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
                ->sum('subtotal');
        });

        return $products->map(fn($p) => [
            'id'          => $p->id,
            'name'        => $p->name,
            'main_image'  => $p->main_image,
            'price'       => (float) ($p->price ?? 0),
            'sales_count' => (int) $p->sales_count,
            'revenue'     => round($p->revenue, 2),
        ])->toArray();
    }

    // ─── Recent orders ────────────────────────────────────────────────────────

    private function recentOrders(int $orgId): array
    {
        return Order::whereHas('items', function ($q) use ($orgId) {
            $q->whereHas('product', fn($p) => $p->where('organization_id', $orgId));
        })
            ->with('user:id,name,email')
            ->latest()
            ->limit(7)
            ->get()
            ->map(fn($order) => [
                'id'             => $order->id,
                'order_number'   => $order->order_number,
                'status'         => $order->status,
                'payment_status' => $order->payment_status,
                'total'          => (float) $order->total,
                'customer_name'  => $order->user?->name,
                'created_at'     => $order->created_at->toISOString(),
            ])
            ->toArray();
    }
}
