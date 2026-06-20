<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\AdminOrderDetailResource;
use App\Http\Resources\Order\AdminOrderListResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreOrderController extends Controller
{
    /**
     * List orders that contain products from this store owner's organization.
     * Store owners only see orders relevant to their products.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Get orders that have at least one item from this organization's products
        $orders = Order::query()
            ->whereHas('items', function ($query) use ($organizationId) {
                $query->whereHas('product', function ($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                });
            })
            ->with(['user', 'items.product', 'orderAddress', 'latestTransaction'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('payment_status'), fn($q, $status) => $q->where('payment_status', $status))
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => AdminOrderListResource::collection($orders),
            'meta'    => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * Show order details - only if it contains products from this store.
     */
    public function show(Order $order): JsonResponse
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Check if this order contains any products from this store
        $hasStoreProducts = $order->items()
            ->whereHas('product', fn($q) => $q->where('organization_id', $organizationId))
            ->exists();

        if (!$hasStoreProducts) {
            return response()->json([
                'success' => false,
                'message' => 'This order does not contain products from your store.',
            ], 403);
        }

        $order->load(['user', 'items.product', 'orderAddress', 'latestTransaction', 'histories.user']);

        return response()->json([
            'success' => true,
            'data'    => new AdminOrderDetailResource($order),
        ]);
    }
}
