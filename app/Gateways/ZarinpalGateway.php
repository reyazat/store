<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZarinpalGateway implements PaymentGatewayInterface
{
    private const SANDBOX_REQUEST_URL = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
    private const SANDBOX_VERIFY_URL  = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
    private const SANDBOX_REDIRECT    = 'https://sandbox.zarinpal.com/pg/StartPay/';

    private const LIVE_REQUEST_URL = 'https://api.zarinpal.com/pg/v4/payment/request.json';
    private const LIVE_VERIFY_URL  = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
    private const LIVE_REDIRECT    = 'https://www.zarinpal.com/pg/StartPay/';

    public function __construct(private readonly PaymentMethod $paymentMethod)
    {
    }

    public function initiate(Order $order): array
    {
        $config     = $this->paymentMethod->config ?? [];
        $merchantId = $config['merchant_id'] ?? '';
        $isSandbox  = (bool) ($config['sandbox'] ?? true);
        $callbackUrl = url('/api/v1/payment/zarinpal/callback');

        $requestUrl = $isSandbox ? self::SANDBOX_REQUEST_URL : self::LIVE_REQUEST_URL;

        // Zarinpal expects amount in IRR (Toman * 10), or pass as Toman with currency param
        $amountToman = (int) $order->total;

        $payload = [
            'merchant_id'  => $merchantId,
            'amount'       => $amountToman,
            'currency'     => 'IRT',
            'description'  => "پرداخت سفارش {$order->order_number}",
            'callback_url' => $callbackUrl,
            'metadata'     => ['order_id' => $order->id],
        ];

        $response = Http::post($requestUrl, $payload);
        $body     = $response->json();

        Log::info('ZarinPal initiate response', ['order' => $order->id, 'body' => $body]);

        $code      = $body['data']['code'] ?? null;
        $authority = $body['data']['authority'] ?? null;

        if ($code !== 100 || !$authority) {
            $message = $body['errors']['message'] ?? 'خطا در اتصال به درگاه پرداخت';
            throw new \RuntimeException($message);
        }

        $transaction = $order->paymentTransactions()->create([
            'driver'           => $this->getDriverName(),
            'gateway_ref'      => $authority,
            'amount'           => $order->total,
            'status'           => 'pending',
            'gateway_response' => $body,
        ]);

        $redirectBase = $isSandbox ? self::SANDBOX_REDIRECT : self::LIVE_REDIRECT;

        return [
            'transaction'  => $transaction,
            'redirect_url' => $redirectBase . $authority,
        ];
    }

    public function verify(PaymentTransaction $transaction, array $callbackData): array
    {
        $status    = $callbackData['Status'] ?? '';
        $authority = $callbackData['Authority'] ?? '';

        if ($status !== 'OK') {
            $transaction->update(['status' => 'cancelled', 'gateway_response' => $callbackData]);
            return ['success' => false, 'message' => 'پرداخت توسط کاربر لغو شد'];
        }

        $config     = $this->paymentMethod->config ?? [];
        $merchantId = $config['merchant_id'] ?? '';
        $isSandbox  = (bool) ($config['sandbox'] ?? true);

        $verifyUrl = $isSandbox ? self::SANDBOX_VERIFY_URL : self::LIVE_VERIFY_URL;

        $response = Http::post($verifyUrl, [
            'merchant_id' => $merchantId,
            'amount'      => (int) $transaction->amount,
            'currency'    => 'IRT',
            'authority'   => $authority,
        ]);

        $body = $response->json();
        $code = $body['data']['code'] ?? null;

        Log::info('ZarinPal verify response', ['transaction' => $transaction->id, 'body' => $body]);

        if ($code === 100 || $code === 101) {
            $refId = (string) ($body['data']['ref_id'] ?? '');
            $transaction->update([
                'status'           => 'paid',
                'paid_at'          => now(),
                'gateway_response' => $body,
                'gateway_ref'      => $refId ?: $authority,
            ]);
            return ['success' => true, 'ref_id' => $refId, 'message' => 'پرداخت با موفقیت انجام شد'];
        }

        $message = $body['errors']['message'] ?? 'پرداخت ناموفق بود';
        $transaction->update(['status' => 'failed', 'gateway_response' => $body]);
        return ['success' => false, 'message' => $message];
    }

    public function getDriverName(): string
    {
        return 'zarinpal';
    }
}
