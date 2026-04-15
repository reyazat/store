<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_status', 'user_id', 'search', 'per_page']);
        $orders = $this->orderRepository->getAllOrders($filters);
        
        return response()->json([
            'success' => true,
            'data' => $orders->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'customer_name' => $order->user?->name,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => $order->total,
                'items_count' => $order->items->count(),
                'created_at' => $order->created_at,
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user', 'items.product', 'items.vendor', 'orderAddress']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'shipping' => $order->shipping,
                'discount' => $order->discount,
                'total' => $order->total,
                'notes' => $order->notes,
                'admin_notes' => $order->admin_notes,
                'items' => $order->items->map(fn($item) => [
                    'product_name' => $item->product_name,
                    'vendor_name' => $item->vendor_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ]),
                'address' => $order->orderAddress ? [
                    'first_name' => $order->orderAddress->first_name,
                    'last_name' => $order->orderAddress->last_name,
                    'phone' => $order->orderAddress->phone,
                    'address' => $order->orderAddress->address,
                    'city' => $order->orderAddress->city,
                    'province' => $order->orderAddress->province,
                    'postal_code' => $order->orderAddress->postal_code,
                ] : null,
                'created_at' => $order->created_at,
                'paid_at' => $order->paid_at,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
                'cancelled_at' => $order->cancelled_at,
            ],
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);
        
        $this->orderService->updateOrderStatus($order, $validated['status']);
        
        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش به‌روزرسانی شد',
            'data' => $order->fresh(),
        ]);
    }

    public function updatePaymentStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
        ]);
        
        $this->orderService->updatePaymentStatus($order, $validated['payment_status']);
        
        return response()->json([
            'success' => true,
            'message' => 'وضعیت پرداخت به‌روزرسانی شد',
            'data' => $order->fresh(),
        ]);
    }

    public function updateNotes(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
        ]);
        
        $order->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'یادداشت ادمین به‌روزرسانی شد',
            'data' => $order,
        ]);
    }
}
