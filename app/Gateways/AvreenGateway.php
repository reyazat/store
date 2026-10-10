<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\Avreen\RsaSignatureService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AvreenGateway implements PaymentGatewayInterface
{
    private const INQUIRY_SIGN_FIELDS = [
        'txnType', 'amount', 'discount', 'totalPayAmount', 'status', 'rrn', 'stan', 'maskPan', 'txnDate',
    ];

    private array $config;

    private RsaSignatureService $rsa;

    public function __construct(private readonly PaymentMethod $paymentMethod)
    {
        $this->config = config('services.avreen', []);
        $this->rsa = new RsaSignatureService($this->config);
    }

    public function initiate(Order $order): array
    {
        $this->assertConfigured();

        $clientTxnId = 'order_'.$order->id.'_'.Str::lower(Str::random(16));
        $transaction = $order->paymentTransactions()->create([
            'driver' => $this->getDriverName(),
            'client_txn_id' => $clientTxnId,
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        try {
            $localDateTime = now((string) ($this->config['timezone'] ?? 'Asia/Tehran'))->format('YmdHis');
            $mid = (string) $this->config['mid'];
            $terminalId = (string) $this->config['terminal_id'];
            $txnType = (string) ($this->config['txn_type_purchase'] ?? '000000');
            $amount = (int) $order->total * 10;
            $signature = $this->rsa->sign("{$mid}&{$localDateTime}&{$txnType}&{$terminalId}&{$amount}&{$clientTxnId}");

            $payload = [
                'mid' => $mid,
                'password' => base64_encode((string) $this->config['password']),
                'localDateTime' => $localDateTime,
                'txnType' => $txnType,
                'terminalId' => $terminalId,
                'amount' => $amount,
                'clientTxnId' => $clientTxnId,
                'redirectUrl' => (string) ($this->config['redirect_url'] ?: url('/api/v1/payment/avreen/callback')),
                'confirmDeadlineDuration' => max(60, (int) ($this->config['confirm_deadline_seconds'] ?? 1800)),
                'signature' => $signature,
                'saleInfo' => ['invoiceNo' => (string) $order->order_number],
            ];

            $order->loadMissing('user');
            if ($order->user?->phone) {
                $payload['mobileNumber'] = $order->user->phone;
            }
            if ($order->user?->email) {
                $payload['emailAddress'] = $order->user->email;
            }

            $body = $this->post('getToken', $payload);
            $token = (string) ($body['token'] ?? '');
            $responseSignature = (string) ($body['signature'] ?? '');

            if ($token === '' || $responseSignature === '' || ! $this->rsa->verify($token, $responseSignature)) {
                throw new RuntimeException('پاسخ دریافت توکن درگاه معتبر نیست.');
            }

            $transaction->update([
                'gateway_token' => $token,
                'gateway_response' => ['token_received' => true],
            ]);

            $redirectUrl = rtrim((string) $this->config['payment_host'], '/')
                .'/api/ipg/site/goPayment?token='.rawurlencode($token)
                .'&lang='.rawurlencode((string) ($this->config['default_language'] ?? 'fa'))
                .'&cbm=POST';

            return ['transaction' => $transaction->fresh(), 'redirect_url' => $redirectUrl];
        } catch (\Throwable $exception) {
            $transaction->update(['status' => 'failed']);
            throw $exception;
        }
    }

    public function verify(PaymentTransaction $transaction, array $callbackData): array
    {
        $status = (string) ($callbackData['status'] ?? '');
        $transaction->loadMissing('order');

        if ($transaction->status === 'paid') {
            return $this->successResult((string) $transaction->gateway_ref);
        }

        if ($status !== 'Ok') {
            if ($transaction->status !== 'cancelled') {
                $transaction->update(['status' => 'cancelled', 'gateway_response' => $this->safeCallbackData($callbackData)]);
            }

            return ['success' => false, 'message' => 'پرداخت توسط کاربر لغو شد'];
        }

        $signature = (string) ($callbackData['signature'] ?? '');
        $signatureValid = $signature !== '' && $this->verifyCallbackSignature($callbackData, $signature);
        $fieldsMatch = $this->callbackFieldsMatch($transaction, $callbackData);

        if (! $signatureValid) {
            $inquiry = $this->inquiry((string) $transaction->gateway_token);
            $inquiryValid = (int) ($inquiry['amount'] ?? 0) === (int) $transaction->amount * 10;

            if ($fieldsMatch && $inquiryValid && ($inquiry['status'] ?? '') === 'payed_confirm') {
                return $this->markPaid($transaction, (string) ($inquiry['rrn'] ?? ''), $inquiry);
            }

            $signatureValid = $inquiryValid && ($inquiry['status'] ?? '') === 'payed_only';
        }

        if (! $signatureValid || ! $fieldsMatch) {
            if ($signatureValid) {
                $this->tokenService('voidPay', (string) $transaction->gateway_token);
            }

            $transaction->update(['status' => 'failed', 'gateway_response' => $this->safeCallbackData($callbackData)]);

            return ['success' => false, 'message' => 'اطلاعات بازگشتی درگاه معتبر نیست'];
        }

        try {
            $confirm = $this->tokenService('confirmPay', (string) $transaction->gateway_token);

            return $this->markPaid($transaction, (string) ($confirm['transactionId'] ?? ''), $callbackData);
        } catch (RuntimeException $exception) {
            $inquiry = $this->inquiry((string) $transaction->gateway_token);
            if (($inquiry['status'] ?? '') === 'payed_confirm'
                && (int) ($inquiry['amount'] ?? 0) === (int) $transaction->amount * 10) {
                return $this->markPaid($transaction, (string) ($inquiry['rrn'] ?? ''), $inquiry);
            }

            throw $exception;
        }
    }

    private function markPaid(PaymentTransaction $transaction, string $reference, array $providerData): array
    {
        $transaction->update([
            'status' => 'paid',
            'paid_at' => now(),
            'gateway_ref' => $reference,
            'gateway_response' => [
                'status' => 'confirmed',
                'rrn' => $providerData['rrn'] ?? null,
                'stan' => $providerData['stan'] ?? null,
                'txn_date' => $providerData['txnDate'] ?? null,
            ],
        ]);

        return $this->successResult($reference);
    }

    private function successResult(string $reference): array
    {
        return ['success' => true, 'ref_id' => $reference, 'message' => 'پرداخت با موفقیت انجام شد'];
    }

    public function decodeRedirectData(string $redirectData): ?array
    {
        $decoded = base64_decode(str_replace(' ', '+', urldecode($redirectData)), true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);

        return is_array($data) ? $data : null;
    }

    public function getDriverName(): string
    {
        return 'avreen';
    }

    private function verifyCallbackSignature(array $data, string $signature): bool
    {
        $fields = explode('&', (string) ($this->config['callback_sign_fields'] ?? 'status&token&mid&txnType&terminalId&amount&clientTxnId&rrn&stan&txnDate'));
        $configured = $this->joinFields($data, $fields);
        $inquiryOrder = $this->joinFields($data, self::INQUIRY_SIGN_FIELDS);

        return $this->rsa->verify($configured, $signature)
            || ($configured !== $inquiryOrder && $this->rsa->verify($inquiryOrder, $signature));
    }

    private function callbackFieldsMatch(PaymentTransaction $transaction, array $data): bool
    {
        return hash_equals((string) $transaction->gateway_token, (string) ($data['token'] ?? ''))
            && hash_equals((string) $transaction->client_txn_id, (string) ($data['clientTxnId'] ?? ''))
            && hash_equals((string) $this->config['mid'], (string) ($data['mid'] ?? ''))
            && hash_equals((string) $this->config['terminal_id'], (string) ($data['terminalId'] ?? ''))
            && hash_equals((string) ($this->config['txn_type_purchase'] ?? '000000'), (string) ($data['txnType'] ?? ''))
            && (int) $transaction->amount * 10 === (int) ($data['amount'] ?? 0);
    }

    private function inquiry(string $token): array
    {
        return $this->tokenService('inquiryPay', $token);
    }

    private function tokenService(string $service, string $token): array
    {
        $mid = (string) $this->config['mid'];
        $body = $this->post($service, [
            'mid' => $mid,
            'password' => base64_encode((string) $this->config['password']),
            'token' => $token,
            'signature' => $this->rsa->sign("{$mid}&{$token}"),
        ]);

        $signature = (string) ($body['signature'] ?? '');
        $signedValue = $service === 'inquiryPay'
            ? $this->joinFields($body, self::INQUIRY_SIGN_FIELDS)
            : (string) ($body['transactionId'] ?? '');

        if ($signature === '' || $signedValue === '' || ! $this->rsa->verify($signedValue, $signature)) {
            throw new RuntimeException('امضای پاسخ درگاه پرداخت معتبر نیست.');
        }

        return $body;
    }

    private function post(string $service, array $payload): array
    {
        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 15))
                ->post(rtrim((string) $this->config['site_host'], '/')."/api/ipg/site/{$service}", $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('اتصال به درگاه پرداخت برقرار نشد.', 0, $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException('درگاه پرداخت درخواست را رد کرد.');
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('پاسخ درگاه پرداخت نامعتبر است.');
        }

        return $body;
    }

    private function joinFields(array $data, array $fields): string
    {
        return implode('&', array_map(static fn (string $field): string => (string) ($data[trim($field)] ?? ''), $fields));
    }

    private function safeCallbackData(array $data): array
    {
        return array_intersect_key($data, array_flip(['status', 'clientTxnId', 'rrn', 'stan', 'txnDate']));
    }

    private function assertConfigured(): void
    {
        foreach (['site_host', 'payment_host', 'mid', 'terminal_id', 'password', 'private_key_path', 'center_public_key_path'] as $key) {
            if (empty($this->config[$key])) {
                throw new RuntimeException('تنظیمات درگاه پرداخت آورین کامل نیست.');
            }
        }
    }
}
