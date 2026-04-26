<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Auth\SendOtpAction;
use IamApi\Actions\Auth\VerifyOtpAction;
use IamApi\Http\Controllers\Controller;
use IamApi\Http\Requests\Mfa\SendOtpRequest;
use IamApi\Http\Requests\Mfa\VerifyOtpRequest;
use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;

/**
 * @group OTP Authentication
 *
 * APIs for OTP (One-Time Password) authentication including sending and verifying OTP codes
 * for various purposes like login, password reset, email verification, and phone verification.
 */
class OtpController extends Controller
{
    public function __construct(
        private readonly SendOtpAction $sendOtpAction,
        private readonly VerifyOtpAction $verifyOtpAction
    ) {}

    /**
     * Send OTP code
     *
     * Send a one-time password (OTP) code to the user's email or phone number.
     * Used for login verification, password reset, email verification, or phone verification.
     *
     * @unauthenticated
     *
     * @bodyParam identifier string required The email address or phone number to send OTP to. Example: user@example.com
     * @bodyParam type string required The type of OTP (login_verification, password_reset, email_verification, phone_verification). Example: login_verification
     * @bodyParam channel string required The channel to send OTP through (email, sms). Example: email
     * @bodyParam captcha string The CAPTCHA value (required after multiple failed attempts). Example: abc123
     * @bodyParam captcha_key string The CAPTCHA key received from /api/v1/captcha endpoint. Example: key_here
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OTP sent successfully",
     *   "data": {
     *     "identifier": "user@example.com",
     *     "type": "login_verification",
     *     "channel": "email",
     *     "expires_at": "2024-01-01T12:10:00.000000Z",
     *     "attempts_remaining": 5
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Failed to send OTP",
     *   "data": {
     *     "error": "User not found or invalid identifier"
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "identifier": ["The identifier field is required."],
     *     "type": ["The selected type is invalid."],
     *     "channel": ["The selected channel is invalid."]
     *   }
     * }
     *
     * @response 429 {
     *   "success": false,
     *   "message": "Too many OTP requests. Please try again later.",
     *   "data": {
     *     "retry_after": 300
     *   }
     * }
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $result = $this->sendOtpAction->execute($request->validated());

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

    /**
     * Verify OTP code
     *
     * Verify a one-time password (OTP) code that was previously sent to the user.
     * Returns success status and may include additional data based on OTP type.
     *
     * @unauthenticated
     *
     * @bodyParam identifier string required The email address or phone number that received the OTP. Example: user@example.com
     * @bodyParam code string required The 6-digit OTP code. Example: 123456
     * @bodyParam type string required The type of OTP being verified. Example: login_verification
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OTP verified successfully",
     *   "data": {
     *     "verified": true,
     *     "type": "login_verification",
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "user@example.com"
     *     },
     *     "tokens": {
     *       "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *       "refresh_token": "def50200e8d4f5c8a2b3c4d5e6f7g8h9...",
     *       "token_type": "bearer",
     *       "expires_in": 900
     *     }
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid or expired OTP code",
     *   "data": {
     *     "attempts_remaining": 2
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "identifier": ["The identifier field is required."],
     *     "code": ["The code must be 6 digits."],
     *     "type": ["The selected type is invalid."]
     *   }
     * }
     *
     * @response 423 {
     *   "success": false,
     *   "message": "Maximum verification attempts exceeded",
     *   "data": {
     *     "locked_until": "2024-01-01T13:00:00.000000Z"
     *   }
     * }
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->verifyOtpAction->execute($request->validated());

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
