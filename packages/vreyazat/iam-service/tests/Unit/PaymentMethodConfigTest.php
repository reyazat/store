<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentMethodConfigTest extends TestCase
{
    #[Test]
    public function config_cast_recovers_legacy_double_encoded_json(): void
    {
        $method = new PaymentMethod;
        $method->setRawAttributes([
            'config' => json_encode(json_encode(['merchant_id' => 'merchant-1'])),
        ]);

        $this->assertSame(['merchant_id' => 'merchant-1'], $method->config);
    }

    #[Test]
    public function config_cast_persists_arrays_as_single_json_document(): void
    {
        $method = new PaymentMethod;
        $method->config = ['sandbox' => true];

        $this->assertSame(['sandbox' => true], $method->config);
        $this->assertSame('{"sandbox":true}', $method->getAttributes()['config']);
    }
}
