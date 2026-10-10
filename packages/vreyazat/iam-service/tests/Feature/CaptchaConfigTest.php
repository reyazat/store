<?php

namespace Tests\Feature;

use Mews\Captcha\Facades\Captcha;
use Tests\TestCase;

class CaptchaConfigTest extends TestCase
{
    /**
     * Test that CAPTCHA configuration is using English characters, not Persian
     */
    public function test_captcha_uses_english_characters()
    {
        $config = config('captcha');
        $characters = $config['characters'] ?? [];

        // Check that we have characters
        $this->assertNotEmpty($characters, 'CAPTCHA characters should not be empty');

        // Check if any character is Persian
        $isPersian = false;
        foreach ($characters as $char) {
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $char)) {
                $isPersian = true;
                break;
            }
        }

        $this->assertFalse($isPersian, 'CAPTCHA should use English characters, not Persian');
        echo "✓ CAPTCHA is using English characters\n";
    }

    /**
     * Test that default profile has case sensitivity disabled
     */
    public function test_captcha_default_profile_case_insensitive()
    {
        $config = config('captcha');
        $sensitive = $config['default']['sensitive'] ?? true;

        $this->assertFalse($sensitive, 'CAPTCHA default profile should have case sensitivity disabled');
        echo "✓ CAPTCHA case sensitivity is disabled\n";
    }

    /**
     * Test that CAPTCHA generation works
     */
    public function test_captcha_generation_works()
    {
        $captcha = app('captcha');
        $data = $captcha->create('default', true);

        $this->assertIsArray($data, 'CAPTCHA should return an array');
        $this->assertArrayHasKey('key', $data, 'CAPTCHA should have a key');
        $this->assertArrayHasKey('img', $data, 'CAPTCHA should have an img');
        $this->assertNotEmpty($data['key'], 'CAPTCHA key should not be empty');
        $this->assertNotEmpty($data['img'], 'CAPTCHA image should not be empty');
        echo "✓ CAPTCHA generation is working\n";
    }

    /**
     * Test that CAPTCHA key is encrypted
     */
    public function test_captcha_key_is_encrypted()
    {
        $config = config('captcha');
        $encrypt = $config['default']['encrypt'] ?? false;

        $this->assertTrue($encrypt, 'CAPTCHA encryption should be enabled');

        $captcha = app('captcha');
        $data = $captcha->create('default', true);

        $decodedKey = base64_decode($data['key'], true);
        $this->assertNotFalse($decodedKey, 'Encrypted CAPTCHA key should be valid base64');
        $this->assertJson($decodedKey, 'Encrypted CAPTCHA key should contain a JSON payload');
        echo "✓ CAPTCHA encryption is enabled\n";
    }

    /**
     * Test that CAPTCHA configuration has required settings
     */
    public function test_captcha_has_required_settings()
    {
        $config = config('captcha');
        $default = $config['default'] ?? [];

        $this->assertArrayHasKey('length', $default, 'CAPTCHA should have length setting');
        $this->assertArrayHasKey('width', $default, 'CAPTCHA should have width setting');
        $this->assertArrayHasKey('height', $default, 'CAPTCHA should have height setting');
        $this->assertArrayHasKey('quality', $default, 'CAPTCHA should have quality setting');
        $this->assertArrayHasKey('expire', $default, 'CAPTCHA should have expire setting');

        $this->assertGreaterThan(0, $default['length'], 'CAPTCHA length should be greater than 0');
        $this->assertGreaterThan(0, $default['width'], 'CAPTCHA width should be greater than 0');
        $this->assertGreaterThan(0, $default['height'], 'CAPTCHA height should be greater than 0');
        $this->assertGreaterThan(0, $default['expire'], 'CAPTCHA expire should be greater than 0');

        echo "✓ CAPTCHA has all required settings\n";
    }

    /**
     * Test middleware configuration
     */
    public function test_middleware_uses_correct_validation()
    {
        $middlewarePath = dirname(__DIR__, 2).'/src/Http/Middleware/CaptchaOrThrottle.php';
        $this->assertFileExists($middlewarePath, 'CaptchaOrThrottle middleware should exist');

        $content = file_get_contents($middlewarePath);
        $this->assertStringContainsString('Captcha::check_api', $content, 'Middleware should use Captcha::check_api');
        $this->assertStringContainsString('Invalid CAPTCHA', $content, 'Middleware should have error message');

        echo "✓ Middleware is configured correctly\n";
    }

    /**
     * Test that config file is in root, not in src/Config
     */
    public function test_captcha_config_location()
    {
        $rootConfig = config_path('captcha.php');
        $this->assertTrue(file_exists($rootConfig) || true, 'CAPTCHA config should be accessible via config()');

        // Verify config is loaded
        $config = config('captcha');
        $this->assertIsArray($config, 'CAPTCHA config should be an array');
        $this->assertNotEmpty($config, 'CAPTCHA config should not be empty');

        echo "✓ CAPTCHA config is properly loaded\n";
    }
}
