<?php

namespace IamApi\Http\Requests\Auth;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array 
    {
        return [
            'refresh_token' => ['required', 'string', new NoHtml()]
        ];
    }

    public function messages(): array
    {
        return [
            'refresh_token.required' => 'Refresh token is required',
            'refresh_token.string' => 'Invalid refresh token format'
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'refresh_token' => [
                'description' => 'The refresh token to generate a new access token',
                'example' => 'eyJ0eXAiOiJKV1QiLCJhbGc...',
            ],
        ];
    }
}
