<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Http\Resources\Order\OrderDetailResource;
use App\Http\Resources\Order\OrderListResource;
use App\Models\Address;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CartService $cartService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getUserOrders(
            $request->user(),
            $request->only(['status', 'per_page'])
        );

        return response()->json([
            'success' => true,
            'data'    => OrderListResource::collection($orders),
            'meta'    => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }

        $order->load(['items', 'orderAddress', 'latestTransaction']);

        return response()->json([
            'success' => true,
            'data'    => new OrderDetailResource($order),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(OrderStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = $request->user();
        $cart      = $this->cartService->getOrCreateCart($user);

        if ($cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'سبد خرید خالی است',
            ], 400);
        }

        $address = Address::findOrFail($validated['address_id']);

        if ($address->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }

        $order = $this->orderService->createOrderFromCart($user, $cart, $address, $validated);

        return response()->json([
            'success' => true,
            'message' => 'سفارش با موفقیت ثبت شد',
            'data'    => [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'total'        => $order->total,
            ],
        ], 201);
    }
}
