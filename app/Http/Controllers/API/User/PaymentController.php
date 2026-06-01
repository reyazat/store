<?php

namespace App\Http\Controllers\API\User;

use App\Actions\Payment\InitiatePaymentAction;
use App\Actions\Payment\VerifyPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Http\Requests\Payment\UploadReceiptRequest;
use App\Http\Resources\Payment\PaymentMethodResource;
use App\Http\Resources\Payment\PaymentTransactionResource;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly InitiatePaymentAction $initiateAction,
        private readonly VerifyPaymentAction   $verifyAction,
        private readonly ImageUploadService    $imageUploadService,
    ) {
    }

    /**
     * Return active payment methods for checkout display.
     */
    public function methods(): JsonResponse
    {
        $methods = PaymentMethod::active()->get();

        return response()->json([
            'success' => true,
            'data'    => PaymentMethodResource::collection($methods),
        ]);
    }

    /**
     * Initiate payment — creates a transaction and returns redirect URL or instructions.
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));

        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'دسترسی غیرمجاز'], 403);
        }

        $currentStatus = OrderStatus::findByKey($order->status);
        if ($currentStatus && ($currentStatus->is_completion || $currentStatus->is_cancellation)) {
            return response()->json(['success' => false, 'message' => 'امکان پرداخت برای این سفارش وجود ندارد'], 422);
        }

        $existingPending = $order->paymentTransactions()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existingPending) {
            $instructions = null;

            if ($existingPending->driver === 'card_to_card') {
                $pm     = PaymentMethod::where('driver', 'card_to_card')->first();
                $config = $pm?->config ?? [];

                $instructions = [
                    'card_number'  => $config['card_number']  ?? '',
                    'card_holder'  => $config['card_holder']  ?? '',
                    'bank_name'    => $config['bank_name']    ?? '',
                    'amount'       => $order->total,
                    'instructions' => $config['instructions'] ?? '',
                ];
            }

            return response()->json([
                'success'      => true,
                'transaction'  => new PaymentTransactionResource($existingPending),
                'redirect_url' => null,
                'instructions' => $instructions,
                'message'      => 'تراکنش در انتظار تأیید وجود دارد',
            ]);
        }

        try {
            $result = $this->initiateAction->execute($order, $request->validated('driver'));
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'      => true,
            'transaction'  => new PaymentTransactionResource($result['transaction']),
            'redirect_url' => $result['redirect_url'] ?? null,
            'instructions' => $result['instructions'] ?? null,
        ]);
    }

    /**
     * Upload card-to-card receipt for a pending transaction.
     */
    public function uploadReceipt(UploadReceiptRequest $request, PaymentTransaction $transaction): JsonResponse
    {
        $transaction->loadMissing('order');
        $order = $transaction->order;

        if (!$order || $order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'دسترسی غیرمجاز'], 403);
        }

        if ($transaction->driver !== 'card_to_card' || $transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'این تراکنش قابل تایید نیست'], 422);
        }

        $path = $this->imageUploadService->store($request->file('receipt'), 'receipts');

        $result = $this->verifyAction->execute($transaction, [
            'receipt_path'   => $path,
            'card_last_four' => $request->input('card_last_four'),
        ]);

        if (!$result['success']) {
            $this->imageUploadService->delete($path);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => new PaymentTransactionResource($transaction->fresh()),
        ]);
    }

    /**
     * Zarinpal callback — verify and redirect to frontend result page.
     */
    public function zarinpalCallback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $authority = $request->input('Authority');
        $status    = $request->input('Status');

        $transaction = PaymentTransaction::where('gateway_ref', $authority)
            ->where('driver', 'zarinpal')
            ->with('order')
            ->first();

        if (!$transaction || !$transaction->order) {
            return redirect(config('app.frontend_url') . '/payment/result?status=error&message=' . urlencode('تراکنش یافت نشد'));
        }

        $result = $this->verifyAction->execute($transaction, $request->all());

        $orderId = $transaction->order_id;
        $orderNumber = $transaction->order->order_number;

        if ($result['success']) {
            return redirect(config('app.frontend_url') . "/payment/result?status=success&order_id={$orderId}&order_number=" . urlencode($orderNumber) . "&ref_id=" . urlencode($result['ref_id'] ?? ''));
        }

        $cancelled = str_contains($status, 'NOK') || $status === '';
        $queryStatus = $cancelled ? 'cancelled' : 'failed';

        return redirect(config('app.frontend_url') . "/payment/result?status={$queryStatus}&order_id={$orderId}&order_number=" . urlencode($orderNumber));
    }
}
