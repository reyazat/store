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
     * Prepare the data for validation.
     * Decode base64 encoded passwords from frontend
     */
    protected function prepareForValidation(): void
    {
        // Decode current_password
        if ($this->has('current_password') && !empty($this->current_password)) {
            $decodedPassword = base64_decode($this->current_password, true);
            
            if ($decodedPassword !== false) {
                $this->merge(['current_password' => $decodedPassword]);
            }
        }

        // Decode new_password
        if ($this->has('new_password') && !empty($this->new_password)) {
            $decodedPassword = base64_decode($this->new_password, true);
            
            if ($decodedPassword !== false) {
                $this->merge(['new_password' => $decodedPassword]);
            }
        }

        // Decode new_password_confirmation
        if ($this->has('new_password_confirmation') && !empty($this->new_password_confirmation)) {
            $decodedPasswordConfirmation = base64_decode($this->new_password_confirmation, true);
            
            if ($decodedPasswordConfirmation !== false) {
                $this->merge(['new_password_confirmation' => $decodedPasswordConfirmation]);
            }
        }
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
