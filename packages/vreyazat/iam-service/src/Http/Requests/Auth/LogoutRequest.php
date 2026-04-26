<?php

namespace IamApi\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array 
    {
        return [
            'logout_all_devices' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'logout_all_devices.boolean' => 'Logout all devices must be true or false'
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'logout_all_devices' => [
                'description' => 'Whether to logout from all devices or just the current one',
                'example' => false,
            ],
        ];
    }
}
