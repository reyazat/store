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

    protected function setUp(): void
    {
        parent::setUp();

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        $publicKey = openssl_pkey_get_details($key)['key'];

        $this->privateKeyPath = tempnam(sys_get_temp_dir(), 'ipg-private-');
        $this->publicKeyPath = tempnam(sys_get_temp_dir(), 'ipg-public-');
        file_put_contents($this->privateKeyPath, $privateKey);
        file_put_contents($this->publicKeyPath, $publicKey);
    }

    #[Test]
    public function it_signs_and_verifies_avreen_payloads(): void
    {
        $service = new RsaSignatureService([
            'private_key_path' => $this->privateKeyPath,
            'center_public_key_path' => $this->publicKeyPath,
            'signature_algorithm' => 'SHA512withRSA',
        ]);

        $signature = $service->sign('merchant&token');

        $this->assertTrue($service->verify('merchant&token', $signature));
        $this->assertFalse($service->verify('merchant&other-token', $signature));
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

        $rsa = new RsaSignatureService(config('services.avreen'));
        Http::fake([
            'https://site.test/api/ipg/site/confirmPay' => Http::response([
                'transactionId' => 'ipg-transaction-1',
                'signature' => $rsa->sign('ipg-transaction-1'),
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
        $callback['signature'] = $rsa->sign(implode('&', array_values($callback)));

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

    protected function tearDown(): void
    {
        @unlink($this->privateKeyPath);
        @unlink($this->publicKeyPath);

        parent::tearDown();
    }
}
