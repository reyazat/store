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
        $order->update($request->validated());

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
        $order->load('histories.user');

        return response()->json([
            'success' => true,
            'data'    => $order->histories->map(fn ($h) => [
                'id'          => $h->id,
                'event'       => $h->event,
                'event_label' => $this->eventLabel($h->event),
                'from_status' => $h->from_status,
                'to_status'   => $h->to_status,
                'note'        => $h->note,
                'actor'       => $h->user ? ['id' => $h->user->id, 'name' => $h->user->name] : null,
                'created_at'  => $h->created_at,
            ]),
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

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'status_changed'      => 'تغییر وضعیت',
            'shipping_code_set'   => 'ثبت کد رهگیری',
            'notification_sent'   => 'ارسال اعلان',
            'payment_confirmed'   => 'تأیید پرداخت',
            'payment_rejected'    => 'رد پرداخت',
            'order_created'       => 'ثبت سفارش',
            default               => $event,
        };
    }
}
