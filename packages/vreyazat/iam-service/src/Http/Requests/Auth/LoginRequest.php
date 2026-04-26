<?php

namespace IamApi\Http\Requests\Auth;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array 
    {
        return [
            'email' => ['required', 'email', 'max:255', new NoHtml()],
            'password' => ['required', 'string'],
            'captcha' => ['nullable', 'string', new NoHtml()],
            'captcha_key' => ['nullable', 'string', new NoHtml()],
            'device_name' => ['nullable', 'string', 'max:255', new NoHtml()],
            'remember_me' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'password.required' => 'Password is required',
            'captcha.string' => 'Invalid captcha format',
            'captcha_key.string' => 'Invalid captcha key format',
            'device_name.max' => 'Device name cannot exceed 255 characters'
        ];
    }

    /**
     * Prepare the data for validation.
     * Decode base64 encoded password from frontend
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('password') && !empty($this->password)) {
            $decodedPassword = base64_decode($this->password, true);
            
            // If base64_decode fails, keep the original password
            if ($decodedPassword !== false) {
                $this->merge(['password' => $decodedPassword]);
            }
        }
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'The user\'s email address',
                'example' => 'user@example.com',
            ],
            'password' => [
                'description' => 'The user\'s password',
                'example' => 'SecurePassword123!',
            ],
            'captcha' => [
                'description' => 'CAPTCHA value (required after multiple failed attempts)',
                'example' => 'abc123',
            ],
            'captcha_key' => [
                'description' => 'CAPTCHA key received from captcha endpoint',
                'example' => 'key_here',
            ],
            'device_name' => [
                'description' => 'The name of the device being used',
                'example' => 'Chrome on Windows',
            ],
            'remember_me' => [
                'description' => 'Whether to extend the refresh token lifetime',
                'example' => true,
            ],
        ];
    }
}
