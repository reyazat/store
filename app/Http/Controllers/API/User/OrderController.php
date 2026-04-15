<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
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
        $user = $request->user();
        $filters = $request->only(['status', 'per_page']);
        
        $orders = $this->orderRepository->getUserOrders($user, $filters);
        
        return response()->json([
            'success' => true,
            'data' => $orders->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
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

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'shipping' => $order->shipping,
                'discount' => $order->discount,
                'total' => $order->total,
                'notes' => $order->notes,
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
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required|integer|exists:addresses,id',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        $user = $request->user();
        
        $cart = $this->cartService->getOrCreateCart($user);
        
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
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total,
            ],
        ], 201);
    }
}
