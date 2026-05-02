<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatus\StoreOrderStatusRequest;
use App\Http\Requests\Admin\OrderStatus\UpdateOrderStatusRequest;
use App\Http\Resources\OrderStatus\OrderStatusResource;
use App\Models\OrderStatus;
use Illuminate\Http\JsonResponse;

class OrderStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => OrderStatusResource::collection(OrderStatus::allOrdered()),
        ]);
    }

    public function store(StoreOrderStatusRequest $request): JsonResponse
    {
        $status = OrderStatus::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش ایجاد شد',
            'data'    => new OrderStatusResource($status),
        ], 201);
    }

    public function update(UpdateOrderStatusRequest $request, OrderStatus $orderStatus): JsonResponse
    {
        $orderStatus->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش به‌روزرسانی شد',
            'data'    => new OrderStatusResource($orderStatus->fresh()),
        ]);
    }

    public function destroy(OrderStatus $orderStatus): JsonResponse
    {
        $orderStatus->delete();

        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش حذف شد',
        ]);
    }
}
