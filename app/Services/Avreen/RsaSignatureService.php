<?php

namespace App\Services\Avreen;

use RuntimeException;

class RsaSignatureService
{
    private int $algorithm;

    public function __construct(private readonly array $config)
    {
        $this->algorithm = match (strtolower(str_replace(['-', '/'], '', (string) ($config['signature_algorithm'] ?? 'SHA512withRSA')))) {
            'sha1withrsa' => OPENSSL_ALGO_SHA1,
            'sha224withrsa' => OPENSSL_ALGO_SHA224,
            'sha256withrsa' => OPENSSL_ALGO_SHA256,
            'sha384withrsa' => OPENSSL_ALGO_SHA384,
            'sha512withrsa' => OPENSSL_ALGO_SHA512,
            default => throw new RuntimeException('الگوریتم امضای درگاه پشتیبانی نمی‌شود.'),
        };
    }

    public function sign(string $data): string
    {
        $key = $this->loadPrivateKey();
        $signature = '';

        if (! openssl_sign($data, $signature, $key, $this->algorithm)) {
            throw new RuntimeException('امضای درخواست درگاه پرداخت ناموفق بود.');
        }

        return base64_encode($signature);
    }

    public function verify(string $data, string $signature): bool
    {
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }

        $result = openssl_verify($data, $decoded, $this->loadCenterPublicKey(), $this->algorithm);

        if ($result === -1) {
            throw new RuntimeException('بررسی امضای درگاه پرداخت ناموفق بود.');
        }

        return $result === 1;
    }

    private function loadPrivateKey(): \OpenSSLAsymmetricKey
    {
        $contents = $this->readKeyFile('private_key_path', 'کلید خصوصی پذیرنده');
        $key = openssl_pkey_get_private($contents);

        if ($key === false) {
            throw new RuntimeException('کلید خصوصی پذیرنده معتبر نیست.');
        }

        return $key;
    }

    private function loadCenterPublicKey(): \OpenSSLAsymmetricKey
    {
        $contents = $this->readKeyFile('center_public_key_path', 'کلید عمومی مرکز');
        $key = openssl_pkey_get_public($contents);

        if ($key === false) {
            throw new RuntimeException('کلید عمومی مرکز پرداخت معتبر نیست.');
        }

        return $key;
    }

    private function readKeyFile(string $configKey, string $label): string
    {
        $path = (string) ($this->config[$configKey] ?? '');

        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("{$label} در دسترس نیست.");
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            throw new RuntimeException("{$label} قابل خواندن نیست.");
        }

        return $contents;
    }
}
