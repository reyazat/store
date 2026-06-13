<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Order\SendOrderNotificationAction;
use App\Actions\Order\SetShippingCodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderSendNotificationRequest;
use App\Http\Requests\Order\OrderSetShippingCodeRequest;
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
        private readonly OrderService              $orderService,
        private readonly OrderRepositoryInterface  $orderRepository,
        private readonly SetShippingCodeAction     $setShippingCodeAction,
        private readonly SendOrderNotificationAction $sendNotificationAction,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getAllOrders(
            $request->only([
                'status', 'payment_status', 'user_id', 'search',
                'date_from', 'date_to', 'min_amount', 'max_amount', 'per_page',
            ])
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
        $order->load(['user', 'items', 'orderAddress', 'latestTransaction', 'histories.user']);

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
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress', 'latestTransaction', 'histories.user'])),
        ]);
    }

    public function updatePaymentStatus(OrderUpdatePaymentStatusRequest $request, Order $order): JsonResponse
    {
        $this->orderService->updatePaymentStatus($order, $request->validated('payment_status'));

        return response()->json([
            'success' => true,
            'message' => 'وضعیت پرداخت به‌روزرسانی شد',
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress', 'latestTransaction', 'histories.user'])),
        ]);
    }

    public function updateNotes(OrderUpdateNotesRequest $request, Order $order): JsonResponse
    {
        // B6 fix: delegate to Service instead of directly calling $order->update() in controller
        $this->orderService->updateAdminNotes($order, $request->validated('admin_notes'));

        return response()->json([
            'success' => true,
            'message' => 'یادداشت ادمین به‌روزرسانی شد',
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress', 'latestTransaction', 'histories.user'])),
        ]);
    }

    public function setShippingCode(OrderSetShippingCodeRequest $request, Order $order): JsonResponse
    {
        $this->setShippingCodeAction->execute($order, $request->validated('shipping_code'));

        return response()->json([
            'success' => true,
            'message' => 'کد رهگیری ثبت شد',
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress', 'latestTransaction', 'histories.user'])),
        ]);
    }

    public function histories(Order $order): JsonResponse
    {
        // B7 fix: reuse the same mapping from AdminOrderDetailResource to avoid drift
        $order->load('histories.user');
        $resource = new AdminOrderDetailResource($order);
        $resourceArray = $resource->toArray(request());

        return response()->json([
            'success' => true,
            'data'    => $resourceArray['histories'] ?? [],
        ]);
    }

    public function sendNotification(OrderSendNotificationRequest $request, Order $order): JsonResponse
    {
        $sent = $this->sendNotificationAction->execute($order, $request->validated('message') ?: null);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'ارسال اعلان ناموفق بود. مشتری حساب کاربری معتبری ندارد.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'اعلان با موفقیت ارسال شد',
            'data'    => new AdminOrderDetailResource($order->fresh(['user', 'items', 'orderAddress', 'latestTransaction', 'histories.user'])),
        ]);
    }


}
