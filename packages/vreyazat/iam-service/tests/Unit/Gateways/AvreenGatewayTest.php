<?php

namespace Tests\Unit\Gateways;

use App\Gateways\AvreenGateway;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\Avreen\RsaSignatureService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvreenGatewayTest extends TestCase
{
    private string $privateKeyPath;

    private string $publicKeyPath;

    private string $centerPrivateKeyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $merchantKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($merchantKey, $merchantPrivateKey);
        $centerKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($centerKey, $centerPrivateKey);
        $centerPublicKey = openssl_pkey_get_details($centerKey)['key'];

        $this->privateKeyPath = tempnam(sys_get_temp_dir(), 'ipg-private-');
        $this->publicKeyPath = tempnam(sys_get_temp_dir(), 'ipg-public-');
        $this->centerPrivateKeyPath = tempnam(sys_get_temp_dir(), 'ipg-center-private-');
        file_put_contents($this->privateKeyPath, $merchantPrivateKey);
        file_put_contents($this->publicKeyPath, $centerPublicKey);
        file_put_contents($this->centerPrivateKeyPath, $centerPrivateKey);
    }

    #[Test]
    public function it_signs_and_verifies_avreen_payloads(): void
    {
        $service = new RsaSignatureService([
            'private_key_path' => $this->privateKeyPath,
            'center_public_key_path' => $this->publicKeyPath,
            'signature_algorithm' => 'SHA512withRSA',
        ]);

        $merchantSignature = $service->sign('merchant&token');
        $centerSignature = $this->signWithCenter('merchant&token');

        $this->assertNotEmpty($merchantSignature);
        $this->assertTrue($service->verify('merchant&token', $centerSignature));
        $this->assertFalse($service->verify('merchant&other-token', $centerSignature));
    }

    #[Test]
    public function payment_service_resolves_avreen_driver(): void
    {
        $orderService = $this->createMock(OrderService::class);
        $paymentService = new PaymentService($orderService);
        $method = new PaymentMethod(['driver' => 'avreen']);

        $this->assertInstanceOf(AvreenGateway::class, $paymentService->resolveGateway($method));
    }

    #[Test]
    public function successful_callback_is_confirmed_immediately(): void
    {
        config()->set('services.avreen', [
            'site_host' => 'https://site.test',
            'payment_host' => 'https://pay.test',
            'mid' => '123456',
            'terminal_id' => '70000003',
            'password' => 'secret',
            'private_key_path' => $this->privateKeyPath,
            'center_public_key_path' => $this->publicKeyPath,
            'signature_algorithm' => 'SHA512withRSA',
            'txn_type_purchase' => '000000',
        ]);

        Http::fake([
            'https://site.test/api/ipg/site/confirmPay' => Http::response([
                'transactionId' => 'ipg-transaction-1',
                'signature' => $this->signWithCenter('ipg-transaction-1'),
            ]),
        ]);

        $callback = [
            'status' => 'Ok',
            'token' => 'token-1',
            'mid' => '123456',
            'txnType' => '000000',
            'terminalId' => '70000003',
            'amount' => 1250000,
            'clientTxnId' => 'order_1_test',
            'rrn' => '123456789012',
            'stan' => '123456',
            'txnDate' => '20261010120000',
        ];
        $callback['signature'] = $this->signWithCenter(implode('&', array_values($callback)));

        $transaction = Mockery::mock(PaymentTransaction::class)->makePartial();
        $transaction->forceFill([
            'gateway_token' => 'token-1',
            'client_txn_id' => 'order_1_test',
            'amount' => 125000,
        ]);
        $transaction->shouldReceive('loadMissing')->once();
        $transaction->shouldReceive('update')->once()->with(Mockery::on(
            fn (array $attributes): bool => $attributes['status'] === 'paid'
                && $attributes['gateway_ref'] === 'ipg-transaction-1'
        ))->andReturnTrue();

        $gateway = new AvreenGateway(new PaymentMethod(['driver' => 'avreen']));
        $result = $gateway->verify($transaction, $callback);

        $this->assertTrue($result['success']);
        $this->assertSame('ipg-transaction-1', $result['ref_id']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function duplicate_paid_callback_does_not_call_provider_again(): void
    {
        $this->configureAvreen();
        Http::fake();

        $transaction = Mockery::mock(PaymentTransaction::class)->makePartial();
        $transaction->forceFill(['status' => 'paid', 'gateway_ref' => 'confirmed-1']);
        $transaction->shouldReceive('loadMissing')->once();
        $transaction->shouldNotReceive('update');

        $result = (new AvreenGateway(new PaymentMethod(['driver' => 'avreen'])))->verify($transaction, ['status' => 'Ok']);

        $this->assertTrue($result['success']);
        $this->assertSame('confirmed-1', $result['ref_id']);
        Http::assertNothingSent();
    }

    #[Test]
    public function confirm_failure_recovers_confirmed_payment_with_inquiry(): void
    {
        $this->configureAvreen();
        $callback = $this->callbackData();
        $inquiry = [
            'txnType' => '000000',
            'amount' => 1250000,
            'discount' => 0,
            'totalPayAmount' => 1250000,
            'status' => 'payed_confirm',
            'rrn' => '123456789012',
            'stan' => '123456',
            'maskPan' => '603799******1234',
            'txnDate' => '20261010120000',
        ];
        $inquiry['signature'] = $this->signWithCenter(implode('&', array_values($inquiry)));

        Http::fake([
            'https://site.test/api/ipg/site/confirmPay' => Http::response(['errorCode' => 'already_confirmed'], 403),
            'https://site.test/api/ipg/site/inquiryPay' => Http::response($inquiry),
        ]);

        $transaction = $this->pendingTransactionMock();
        $transaction->shouldReceive('update')->once()->with(Mockery::on(
            fn (array $attributes): bool => $attributes['status'] === 'paid'
                && $attributes['gateway_ref'] === '123456789012'
        ))->andReturnTrue();

        $result = (new AvreenGateway(new PaymentMethod(['driver' => 'avreen'])))->verify($transaction, $callback);

        $this->assertTrue($result['success']);
        $this->assertSame('123456789012', $result['ref_id']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function invalid_callback_signature_is_reconciled_before_confirmation(): void
    {
        $this->configureAvreen();
        $callback = $this->callbackData();
        $callback['signature'] = 'invalid-signature';
        $inquiry = $this->signedInquiry('payed_only');

        Http::fake([
            'https://site.test/api/ipg/site/inquiryPay' => Http::response($inquiry),
            'https://site.test/api/ipg/site/confirmPay' => Http::response([
                'transactionId' => 'confirmed-after-inquiry',
                'signature' => $this->signWithCenter('confirmed-after-inquiry'),
            ]),
        ]);

        $transaction = $this->pendingTransactionMock();
        $transaction->shouldReceive('update')->once()->with(Mockery::on(
            fn (array $attributes): bool => $attributes['status'] === 'paid'
                && $attributes['gateway_ref'] === 'confirmed-after-inquiry'
        ))->andReturnTrue();

        $result = (new AvreenGateway(new PaymentMethod(['driver' => 'avreen'])))->verify($transaction, $callback);

        $this->assertTrue($result['success']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function valid_callback_with_amount_mismatch_is_voided(): void
    {
        $this->configureAvreen();
        $callback = $this->callbackData();
        $callback['amount'] = 2500000;
        $callback['signature'] = $this->signWithCenter(implode('&', array_values(array_diff_key($callback, ['signature' => true]))));

        Http::fake([
            'https://site.test/api/ipg/site/voidPay' => Http::response([
                'transactionId' => 'void-transaction-1',
                'signature' => $this->signWithCenter('void-transaction-1'),
            ]),
        ]);

        $transaction = $this->pendingTransactionMock();
        $transaction->shouldReceive('update')->once()->with(Mockery::on(
            fn (array $attributes): bool => $attributes['status'] === 'failed'
        ))->andReturnTrue();

        $result = (new AvreenGateway(new PaymentMethod(['driver' => 'avreen'])))->verify($transaction, $callback);

        $this->assertFalse($result['success']);
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/voidPay'));
    }

    #[Test]
    public function cancelled_callback_does_not_call_confirm_or_void(): void
    {
        $this->configureAvreen();
        Http::fake();

        $transaction = $this->pendingTransactionMock();
        $transaction->shouldReceive('update')->once()->with(Mockery::on(
            fn (array $attributes): bool => $attributes['status'] === 'cancelled'
        ))->andReturnTrue();

        $result = (new AvreenGateway(new PaymentMethod(['driver' => 'avreen'])))->verify($transaction, ['status' => 'Cancel']);

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    private function configureAvreen(): void
    {
        config()->set('services.avreen', [
            'site_host' => 'https://site.test',
            'payment_host' => 'https://pay.test',
            'mid' => '123456',
            'terminal_id' => '70000003',
            'password' => 'secret',
            'private_key_path' => $this->privateKeyPath,
            'center_public_key_path' => $this->publicKeyPath,
            'signature_algorithm' => 'SHA512withRSA',
            'txn_type_purchase' => '000000',
        ]);

    }

    private function callbackData(): array
    {
        $callback = [
            'status' => 'Ok',
            'token' => 'token-1',
            'mid' => '123456',
            'txnType' => '000000',
            'terminalId' => '70000003',
            'amount' => 1250000,
            'clientTxnId' => 'order_1_test',
            'rrn' => '123456789012',
            'stan' => '123456',
            'txnDate' => '20261010120000',
        ];
        $callback['signature'] = $this->signWithCenter(implode('&', array_values($callback)));

        return $callback;
    }

    private function signedInquiry(string $status): array
    {
        $inquiry = [
            'txnType' => '000000',
            'amount' => 1250000,
            'discount' => 0,
            'totalPayAmount' => 1250000,
            'status' => $status,
            'rrn' => '123456789012',
            'stan' => '123456',
            'maskPan' => '603799******1234',
            'txnDate' => '20261010120000',
        ];
        $inquiry['signature'] = $this->signWithCenter(implode('&', array_values($inquiry)));

        return $inquiry;
    }

    private function signWithCenter(string $data): string
    {
        return (new RsaSignatureService([
            'private_key_path' => $this->centerPrivateKeyPath,
            'center_public_key_path' => $this->publicKeyPath,
            'signature_algorithm' => 'SHA512withRSA',
        ]))->sign($data);
    }

    private function pendingTransactionMock(): PaymentTransaction
    {
        $transaction = Mockery::mock(PaymentTransaction::class)->makePartial();
        $transaction->forceFill([
            'status' => 'pending',
            'gateway_token' => 'token-1',
            'client_txn_id' => 'order_1_test',
            'amount' => 125000,
        ]);
        $transaction->shouldReceive('loadMissing')->once();

        return $transaction;
    }

    protected function tearDown(): void
    {
        @unlink($this->privateKeyPath);
        @unlink($this->publicKeyPath);
        @unlink($this->centerPrivateKeyPath);

        parent::tearDown();
    }
}
