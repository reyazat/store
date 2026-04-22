<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderUpdateNotesRequest;
use App\Http\Requests\Order\OrderUpdatePaymentStatusRequest;
use App\Http\Requests\Order\OrderUpdateStatusRequest;
use App\Http\Resources\Order\AdminOrderDetailResource;
use App\Http\Resources\Order\AdminOrderListResource;
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
        $orders = $this->orderRepository->getAllOrders(
            $request->only(['status', 'payment_status', 'user_id', 'search', 'per_page'])
        );

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

    public function show(Order $order): JsonResponse
    {
        $order->load(['user', 'items', 'orderAddress']);

        return response()->json([
            'success' => true,
            'data'    => new AdminOrderDetailResource($order),
        ]);
    }

    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResponse
    {
        $this->orderService->updateOrderStatus($order, $request->validated('status'));

        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش به‌روزرسانی شد',
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress'])),
        ]);
    }

    public function updatePaymentStatus(OrderUpdatePaymentStatusRequest $request, Order $order): JsonResponse
    {
        $this->orderService->updatePaymentStatus($order, $request->validated('payment_status'));

        return response()->json([
            'success' => true,
            'message' => 'وضعیت پرداخت به‌روزرسانی شد',
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress'])),
        ]);
    }

    public function updateNotes(OrderUpdateNotesRequest $request, Order $order): JsonResponse
    {
        $order->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'یادداشت ادمین به‌روزرسانی شد',
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress'])),
        ]);
    }
}
