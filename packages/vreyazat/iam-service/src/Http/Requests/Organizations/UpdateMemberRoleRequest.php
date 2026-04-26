<?php

namespace IamApi\Http\Requests\Organizations;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:owner,admin,user,member', new NoHtml()],
        ];
    }

    public function messages(): array
    {
        return [
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
            'role' => [
                'description' => 'The organization role for the member. Options: owner (full access), admin (management access), user (employee access), member (customer access)',
                'example' => 'admin',
            ],
        ];
    }
}
