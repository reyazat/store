<?php

namespace IamApi\Actions\Captcha;

use IamApi\Actions\BaseAction;
use Illuminate\Support\Str;

/**
 * GetCaptchaAction
 *
 * Generates a new CAPTCHA challenge with a unique key and image.
 * The CAPTCHA key is used to validate the user's response later.
 */
class GetCaptchaAction extends BaseAction
{
    /**
     * Execute the action to generate a new CAPTCHA.
     *
     * @param array $data Array containing optional 'config' parameter
     *                    - config: string (optional) CAPTCHA configuration name, defaults to 'default'
     *
     * @return array Success response with CAPTCHA key and image
     */
    public function execute(array $data = []): array
    {
        try {
            $config = $data['config'] ?? 'default';

            // Generate CAPTCHA using the captcha service with api=true
            // This returns an array with 'key' and 'img' (as data URI)
            // Note: We must use app('captcha')->create($config, true) directly
            // because the captcha() helper doesn't support the api parameter
            $captchaData = app('captcha')->create($config, true);

            return $this->success([
                'key' => $captchaData['key'],
                'img' => $captchaData['img'],
            ], 'CAPTCHA generated successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to generate CAPTCHA',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
