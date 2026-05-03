<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Payment\ConfirmReceiptAction;
use App\Actions\Payment\RejectReceiptAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Order\AdminOrderDetailResource;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly ConfirmReceiptAction $confirmAction,
        private readonly RejectReceiptAction  $rejectAction
    ) {
    }

    public function confirmReceipt(PaymentTransaction $transaction): JsonResponse
    {
        $transaction->loadMissing('order');

        if ($transaction->driver !== 'card_to_card' || $transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'این تراکنش قابل تأیید نیست',
            ], 422);
        }

        if (!$transaction->order) {
            return response()->json(['success' => false, 'message' => 'سفارش مرتبط یافت نشد'], 404);
        }

        $order = $this->confirmAction->execute($transaction);

        return response()->json([
            'success' => true,
            'message' => 'رسید پرداخت تأیید شد و سفارش به مرحله پردازش منتقل گردید',
            'data'    => new AdminOrderDetailResource($order->load(['user', 'items', 'orderAddress', 'latestTransaction'])),
        ]);
    }

    public function rejectReceipt(PaymentTransaction $transaction): JsonResponse
    {
        $transaction->loadMissing('order');

        if ($transaction->driver !== 'card_to_card' || $transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'این تراکنش قابل رد نیست',
            ], 422);
        }

        if (!$transaction->order) {
            return response()->json(['success' => false, 'message' => 'سفارش مرتبط یافت نشد'], 404);
        }

        $order = $this->rejectAction->execute($transaction);

        return response()->json([
            'success' => true,
            'message' => 'رسید پرداخت رد شد',
            'data'    => new AdminOrderDetailResource($order->load(['user', 'items', 'orderAddress', 'latestTransaction'])),
        ]);
    }
}
