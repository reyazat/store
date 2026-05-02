<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Resources\Payment\AdminPaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $methods = PaymentMethod::orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data'    => AdminPaymentMethodResource::collection($methods),
        ]);
    }

    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new AdminPaymentMethodResource($paymentMethod),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'روش پرداخت به‌روزرسانی شد',
            'data'    => new AdminPaymentMethodResource($paymentMethod->fresh()),
        ]);
    }
}
