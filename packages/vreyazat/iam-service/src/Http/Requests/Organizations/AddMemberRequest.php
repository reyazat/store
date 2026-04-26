<?php

namespace IamApi\Http\Requests\Organizations;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_uuid' => ['required_without:email', 'string', 'uuid', 'exists:users,uuid'],
            'email' => ['required_without:user_uuid', 'email', 'max:255', new NoHtml()],
            'role' => ['required', 'string', 'in:owner,admin,user,member', new NoHtml()],
        ];
    }

    public function messages(): array
    {
        return [
            'user_uuid.required_without' => 'Either user UUID or email is required',
            'user_uuid.uuid' => 'Invalid user UUID format',
            'user_uuid.exists' => 'User not found',
            'email.required_without' => 'Either email or user UUID is required',
            'email.email' => 'Please provide a valid email address',
            'role.required' => 'Role is required',
            'role.in' => 'Role must be one of: owner, admin, user, member',
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'user_uuid' => [
                'description' => 'The UUID of the user to add (required without email)',
                'example' => '550e8400-e29b-41d4-a716-446655440000',
            ],
            'email' => [
                'description' => 'The email of the user to add (required without user_id)',
                'example' => 'newuser@example.com',
            ],
            'role' => [
                'description' => 'The organization role to assign. Options: owner (full access), admin (management access), user (employee access), member (customer access)',
                'example' => 'member',
            ],
        ];
    }
}
