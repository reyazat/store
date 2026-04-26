<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Mfa\DisableMfaAction;
use IamApi\Rules\NoHtml;
use IamApi\Actions\Mfa\EnableMfaAction;
use IamApi\Actions\Mfa\GenerateRecoveryCodesAction;
use IamApi\Actions\Mfa\GetMfaStatusAction;
use IamApi\Actions\Mfa\GetRecoveryCodesAction;
use IamApi\Actions\Mfa\VerifyMfaAction;
use IamApi\Actions\Mfa\VerifyMfaLoginAction;
use IamApi\Actions\Mfa\VerifyRecoveryCodeAction;
use IamApi\Http\Controllers\Controller;
use IamApi\Http\Requests\Mfa\EnableMfaRequest;
use IamApi\Http\Requests\Mfa\VerifyMfaRequest;
use IamApi\Http\Requests\Mfa\VerifyRecoveryCodeRequest;
use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Multi-Factor Authentication
 *
 * APIs for Multi-Factor Authentication (MFA) management including TOTP setup,
 * verification, recovery codes generation, and MFA status management.
 */
class MfaController extends Controller
{
    public function __construct(
        private readonly EnableMfaAction $enableMfaAction,
        private readonly DisableMfaAction $disableMfaAction,
        private readonly VerifyMfaAction $verifyMfaAction,
        private readonly GenerateRecoveryCodesAction $generateRecoveryCodesAction,
        private readonly VerifyRecoveryCodeAction $verifyRecoveryCodeAction,
        private readonly GetMfaStatusAction $getMfaStatusAction,
        private readonly VerifyMfaLoginAction $verifyMfaLoginAction,
        private readonly GetRecoveryCodesAction $getRecoveryCodesAction
    ) {}

    /**
     * Get MFA status
     *
     * Get the current Multi-Factor Authentication status for the authenticated user,
     * including TOTP setup status and recovery codes information.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "MFA status retrieved successfully",
     *   "data": {
     *     "mfa_enabled": true,
     *     "totp_configured": true,
     *     "recovery_codes_count": 8,
     *     "backup_codes_generated_at": "2024-01-01T12:00:00.000000Z",
     *     "last_used_at": "2024-01-01T10:30:00.000000Z"
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     */
    public function status(): JsonResponse
    {
        $result = $this->getMfaStatusAction->execute([
            'user' => auth()->user()
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

    /**
     * Enable MFA
     *
     * Enable Multi-Factor Authentication for the authenticated user. This generates
     * a TOTP secret and QR code for setup with authenticator apps like Google Authenticator.
     *
     * @authenticated
     *
     * @bodyParam current_password string required The user's current password for security verification. Example: CurrentPassword123!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "MFA setup initiated. Please scan the QR code with your authenticator app.",
     *   "data": {
     *     "secret": "JBSWY3DPEHPK3PXP",
     *     "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEA...",
     *     "backup_codes": [
     *       "12345678",
     *       "87654321",
     *       "11223344",
     *       "44332211",
     *       "55667788",
     *       "88776655",
     *       "99001122",
     *       "22110099",
     *       "33445566",
     *       "66554433"
     *     ],
     *     "setup_instructions": "1. Install Google Authenticator or similar app\n2. Scan the QR code or enter the secret manually\n3. Enter the 6-digit code to verify setup\n4. Save your recovery codes in a secure location"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "MFA is already enabled for this account",
     *   "data": null
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid current password",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "current_password": ["The current password field is required."]
     *   }
     * }
     */
    public function enable(EnableMfaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->enableMfaAction->execute([
            'user' => auth()->user(),
            'current_password' => $data['current_password']
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

    /**
     * Verify MFA setup
     *
     * Verify the TOTP code during MFA setup to complete the Multi-Factor Authentication
     * configuration. This confirms that the user can generate valid codes.
     *
     * @authenticated
     *
     * @bodyParam code string required The 6-digit TOTP code from the authenticator app. Example: 123456
     *
     * @response 200 {
     *   "success": true,
     *   "message": "MFA enabled successfully",
     *   "data": {
     *     "mfa_enabled": true,
     *     "enabled_at": "2024-01-01T12:00:00.000000Z",
     *     "recovery_codes_count": 10
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid TOTP code",
     *   "data": {
     *     "attempts_remaining": 2
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "code": ["The code must be 6 digits."]
     *   }
     * }
     */
    public function verify(VerifyMfaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->verifyMfaAction->execute([
            'user' => auth()->user(),
            'code' => $data['code']
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

    /**
     * Disable MFA
     *
     * Disable Multi-Factor Authentication for the authenticated user. This removes
     * TOTP configuration and invalidates all recovery codes.
     *
     * @authenticated
     *
     * @bodyParam current_password string required The user's current password for security verification. Example: CurrentPassword123!
     * @bodyParam confirmation string required Type "DISABLE MFA" to confirm the action. Example: DISABLE MFA
     *
     * @response 200 {
     *   "success": true,
     *   "message": "MFA disabled successfully",
     *   "data": {
     *     "mfa_enabled": false,
     *     "disabled_at": "2024-01-01T12:00:00.000000Z"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "MFA is not enabled for this account",
     *   "data": null
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid current password",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "current_password": ["The current password field is required."],
     *     "confirmation": ["Please type 'DISABLE MFA' to confirm."]
     *   }
     * }
     */
    public function disable(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', new NoHtml()],
            'confirmation' => ['required', 'string', 'in:DISABLE MFA', new NoHtml()]
        ], [
            'confirmation.in' => 'Please type "DISABLE MFA" to confirm.'
        ]);

        $result = $this->disableMfaAction->execute([
            'user' => auth()->user(),
            'current_password' => $data['current_password'],
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

    /**
     * Generate new recovery codes
     *
     * Generate new recovery codes for Multi-Factor Authentication. This invalidates
     * all existing recovery codes and creates 10 new ones.
     *
     * @authenticated
     *
     * @bodyParam current_password string required The user's current password for security verification. Example: CurrentPassword123!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "New recovery codes generated successfully",
     *   "data": {
     *     "recovery_codes": [
     *       "12345678",
     *       "87654321",
     *       "11223344",
     *       "44332211",
     *       "55667788",
     *       "88776655",
     *       "99001122",
     *       "22110099",
     *       "33445566",
     *       "66554433"
     *     ],
     *     "generated_at": "2024-01-01T12:00:00.000000Z",
     *     "warning": "These codes can only be used once each. Store them securely."
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "MFA is not enabled for this account",
     *   "data": null
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid current password",
     *   "data": null
     * }
     */
    public function generateRecoveryCodes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', new NoHtml()]
        ]);

        $result = $this->generateRecoveryCodesAction->execute([
            'user' => auth()->user(),
            'current_password' => $data['current_password']
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

    /**
     * Verify recovery code
     *
     * Verify a recovery code for Multi-Factor Authentication. Recovery codes can be used
     * as an alternative to TOTP codes when the authenticator app is not available.
     *
     * @authenticated
     *
     * @bodyParam recovery_code string required The 8-character recovery code. Example: 12345678
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Recovery code verified successfully",
     *   "data": {
     *     "verified": true,
     *     "remaining_codes": 7,
     *     "used_at": "2024-01-01T12:00:00.000000Z",
     *     "warning": "This recovery code has been used and is no longer valid."
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid or already used recovery code",
     *   "data": {
     *     "remaining_codes": 8
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "recovery_code": ["The recovery code must be 8 characters."]
     *   }
     * }
     */
    public function verifyRecoveryCode(VerifyRecoveryCodeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->verifyRecoveryCodeAction->execute([
            'user' => auth()->user(),
            'recovery_code' => $data['recovery_code']
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

    /**
     * Verify MFA during login
     *
     * Verify TOTP code during login process after initial authentication.
     * This endpoint is used in the MFA login flow to complete authentication.
     *
     * @unauthenticated
     *
     * @bodyParam login_token string required The temporary login token from initial authentication. Example: temp_login_token_here
     * @bodyParam code string required The 6-digit TOTP code from authenticator app. Example: 123456
     *
     * @response 200 {
     *   "success": true,
     *   "message": "MFA verification successful",
     *   "data": {
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
     *   "message": "Invalid TOTP code",
     *   "data": {
     *     "attempts_remaining": 2
     *   }
     * }
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login_token' => ['required', 'string', new NoHtml()],
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/', new NoHtml()]
        ]);

        $result = $this->verifyMfaLoginAction->execute([
            'login_token' => $data['login_token'],
            'code' => $data['code'],
            'type' => 'totp'
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

    /**
     * Verify recovery code during login
     *
     * Verify recovery code during login process as an alternative to TOTP.
     * This endpoint is used when the user cannot access their authenticator app.
     *
     * @unauthenticated
     *
     * @bodyParam login_token string required The temporary login token from initial authentication. Example: temp_login_token_here
     * @bodyParam recovery_code string required The 8-character recovery code. Example: 12345678
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Recovery code verification successful",
     *   "data": {
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
     *     },
     *     "recovery_code_used": true,
     *     "remaining_codes": 7
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid or already used recovery code",
     *   "data": {
     *     "remaining_codes": 8
     *   }
     * }
     */
    public function verifyRecoveryLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login_token' => ['required', 'string', new NoHtml()],
            'recovery_code' => ['required', 'string', 'size:10', 'regex:/^[A-Za-z0-9]{10}$/', new NoHtml()]
        ]);

        $result = $this->verifyMfaLoginAction->execute([
            'login_token' => $data['login_token'],
            'recovery_code' => $data['recovery_code'],
            'type' => 'recovery_code'
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

    /**
     * Get recovery codes
     *
     * Get the current recovery codes for the authenticated user. Shows masked
     * version of codes unless they were just generated.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Recovery codes retrieved successfully",
     *   "data": {
     *     "recovery_codes": [
     *       "****5678",
     *       "****4321",
     *       "****3344",
     *       "****2211",
     *       "****7788",
     *       "****6655",
     *       "****0122",
     *       "****1099"
     *     ],
     *     "total_codes": 10,
     *     "used_codes": 2,
     *     "remaining_codes": 8,
     *     "generated_at": "2024-01-01T12:00:00.000000Z",
     *     "warning": "Recovery codes are shown masked for security. Generate new codes to see full codes."
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "MFA is not enabled for this account",
     *   "data": null
     * }
     */
    public function getRecoveryCodes(): JsonResponse
    {
        $result = $this->getRecoveryCodesAction->execute([
            'user' => auth()->user()
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
