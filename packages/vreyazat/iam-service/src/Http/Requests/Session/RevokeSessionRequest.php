<?php

namespace IamApi\Http\Requests\Session;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class RevokeSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jti' => ['required', 'string', 'uuid', new NoHtml()]
        ];
    }

    public function messages(): array
    {
        return [
            'jti.required' => 'Session identifier (JTI) is required',
            'jti.string' => 'Session identifier must be a string',
            'jti.uuid' => 'Session identifier must be a valid UUID'
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'jti' => [
                'description' => 'The JWT ID (JTI) of the session to revoke',
                'example' => '550e8400-e29b-41d4-a716-446655440000',
            ],
        ];
    }
}
