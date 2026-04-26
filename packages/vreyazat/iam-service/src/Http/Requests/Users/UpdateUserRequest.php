<?php

namespace IamApi\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use IamApi\Rules\NoHtml;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array 
    {
        $userId = Auth::id();
        
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', new NoHtml()],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $userId, new NoHtml()],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/', 'max:20', 'unique:users,phone,' . $userId, new NoHtml()]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.max' => 'Name cannot exceed 255 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already taken',
            'phone.regex' => 'Phone number must be in valid international format (E.164)',
            'phone.unique' => 'This phone number is already taken',
            'phone.max' => 'Phone number cannot exceed 20 characters'
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
                'description' => 'The user\'s phone number',
                'example' => '+989123456789',
            ],
        ];
    }
}
