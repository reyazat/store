<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Captcha\GetCaptchaAction;
use IamApi\Http\Controllers\Controller;
use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;

/**
 * @group CAPTCHA
 *
 * APIs for CAPTCHA challenge generation and validation.
 * CAPTCHA is used to protect public endpoints from automated attacks and brute force attempts.
 */
class CaptchaController extends Controller
{
    public function __construct(
        private readonly GetCaptchaAction $getCaptchaAction
    ) {}

    /**
     * Get CAPTCHA challenge
     *
     * Generate a new CAPTCHA challenge with a unique key and image.
     * The returned key should be included in subsequent requests that require CAPTCHA verification.
     * This endpoint is rate-limited to prevent abuse.
     *
     * @unauthenticated
     *
     * @queryParam config string The CAPTCHA configuration to use. Example: default
     *
     * @response 200 {
     *   "success": true,
     *   "message": "CAPTCHA generated successfully",
     *   "data": {
     *     "key": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
     *     "img": "<svg>...</svg>"
     *   }
     * }
     *
     * @response 429 {
     *   "success": false,
     *   "message": "Too many CAPTCHA requests. Please try again later.",
     *   "data": null
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "message": "Failed to generate CAPTCHA",
     *   "data": {
     *     "error": "Error message details"
     *   }
     * }
     */
    public function get(?string $config = 'default'): JsonResponse
    {
        $result = $this->getCaptchaAction->execute([
            'config' => $config,
        ]);

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success($result['data'], $result['message'])
        );
    }
}
