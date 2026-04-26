<?php

namespace IamApi\Actions\Password;

use IamApi\Actions\BaseAction;
use IamApi\Actions\Auth\SendOtpAction;
use Exception;
use Illuminate\Support\Facades\Log;

class ForgotPasswordAction extends BaseAction
{
    public function __construct(
        public SendOtpAction $sendOtpAction
    ) {}

    /**
     * Execute the forgot password action
     */
    public function execute(array $data=[]): array
    {
        try {
            // Prepare OTP data for password reset
            $otpData = [
                'identifier' => $data['identifier'],
                'type' => 'password_reset',
                'channel' => $data['channel']
            ];

            // Use the existing SendOtpAction to send password reset OTP
            $result = $this->sendOtpAction->execute($otpData);

            if (!$result['success']) {
                return $result;
            }

            // Customize the response message for password reset context
            return $this->success($result['data'], 'Password reset OTP sent successfully');

        } catch (Exception $e) {
            Log::error('Forgot password failed', [
                'identifier' => $data['identifier'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return $this->error('Failed to send password reset OTP. Please try again later.', null, 500);
        }
    }
}
