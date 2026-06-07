<?php

namespace IamApi\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use IamApi\Rules\NoHtml;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array 
    {
        return [
            'name' => ['required', 'string', 'max:255', new NoHtml()],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new NoHtml()],
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/', 'max:20', 'unique:users,phone', new NoHtml()],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
            'send_verification_email' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.max' => 'Name cannot exceed 255 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'phone.regex' => 'Phone number must be in valid international format (E.164)',
            'phone.unique' => 'This phone number is already registered',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'password_confirmation.required' => 'Password confirmation is required'
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The user\'s full name',
                'example' => 'John Doe',
            ],
            'email' => [
                'description' => 'The user\'s email address',
                'example' => 'john.doe@example.com',
            ],
            'phone' => [
                'description' => 'The user\'s phone number (optional)',
                'example' => '+989123456789',
            ],
            'password' => [
                'description' => 'The user\'s password (min 8 chars, mixed case, numbers, symbols)',
                'example' => 'SecurePassword123!',
            ],
            'password_confirmation' => [
                'description' => 'Password confirmation (must match password)',
                'example' => 'SecurePassword123!',
            ],
            'send_verification_email' => [
                'description' => 'Whether to send verification email',
                'example' => true,
            ],
        ];
    }
}
