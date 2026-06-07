<?php

namespace IamApi\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array 
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'new_password_confirmation' => ['required', 'string'],
            'logout_other_devices' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required',
            'new_password.required' => 'New password is required',
            'new_password.confirmed' => 'New password confirmation does not match',
            'new_password_confirmation.required' => 'New password confirmation is required',
            'logout_other_devices.boolean' => 'Logout other devices must be true or false'
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'current_password' => [
                'description' => 'The user\'s current password',
                'example' => 'CurrentPassword123!',
            ],
            'new_password' => [
                'description' => 'The new password (min 8 chars, mixed case, numbers, symbols)',
                'example' => 'NewSecurePassword123!',
            ],
            'new_password_confirmation' => [
                'description' => 'Password confirmation (must match new_password)',
                'example' => 'NewSecurePassword123!',
            ],
            'logout_other_devices' => [
                'description' => 'Whether to logout from all other devices after password change',
                'example' => true,
            ],
        ];
    }
}
