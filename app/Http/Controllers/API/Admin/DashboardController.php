<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'stats'               => $this->dashboardService->getStats(),
                'recent_orders'       => $this->dashboardService->getRecentOrders(),
                'top_products'        => $this->dashboardService->getTopProducts(),
                'monthly_revenue'     => $this->dashboardService->getMonthlyRevenue(),
                'order_status_breakdown' => $this->dashboardService->getOrderStatusBreakdown(),
                'top_stores'          => $this->dashboardService->getTopStores(),
                'pending_reviews'     => $this->dashboardService->getPendingReviews(),
                'store_growth'        => $this->dashboardService->getStoreGrowth(),
                'user_growth'         => $this->dashboardService->getUserGrowth(),
            ],
        ]);
    }
}
