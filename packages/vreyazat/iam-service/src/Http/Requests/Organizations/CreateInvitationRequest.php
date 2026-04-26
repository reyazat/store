<?php

namespace IamApi\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;
use IamApi\Rules\NoHtml;

class CreateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', new NoHtml()],
            'role' => ['required', 'string', 'in:admin,member', new NoHtml()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'role.required' => 'Role is required',
            'role.in' => 'Role must be either admin or member',
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'The email address of the person to invite',
                'example' => 'newuser@example.com',
            ],
            'role' => [
                'description' => 'The role to assign to the invited user (admin or member)',
                'example' => 'member',
            ],
        ];
    }
}
