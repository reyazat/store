<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderTrackingResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    /**
     * Track an order by its tracking code (order_number).
     * Public endpoint - no authentication required.
     */
    public function track(Request $request): JsonResponse
    {
        $trackingCode = $request->input('tracking_code');

        if (empty($trackingCode)) {
            return response()->json([
                'success' => false,
                'message' => 'کد رهگیری را وارد کنید',
            ], 422);
        }

        $order = Order::where('order_number', $trackingCode)
            ->with(['items', 'orderAddress', 'latestTransaction'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارشی با این کد رهگیری یافت نشد',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new OrderTrackingResource($order),
        ]);
    }
}
