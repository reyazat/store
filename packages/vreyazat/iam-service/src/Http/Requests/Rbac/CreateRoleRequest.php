<?php

namespace IamApi\Http\Requests\Rbac;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IamApi\Rules\NoHtml;

class CreateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Get the organization model from route (resolved via UUID)
        $organization = $this->route('org');
        $organizationId = $organization instanceof \IamApi\Models\Organization ? $organization->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new NoHtml(),
                Rule::unique('roles', 'name')
                    ->where('organization_id', $organizationId)
                    ->where('guard_name', 'api'),
            ],
            'guard_name' => 'sometimes|string|in:api',
            'permissions' => 'sometimes|array',
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')
                    ->where('organization_id', $organizationId)
                    ->where('guard_name', 'api'),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'A role with this name already exists in this organization.',
            'permissions.*.exists' => 'One or more permissions do not exist in this organization.',
        ];
    }

    /**
     * Get the body parameters for API documentation.
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the role.',
                'example' => 'editor',
            ],
            'guard_name' => [
                'description' => 'The guard name for the role. Defaults to "api".',
                'example' => 'api',
            ],
            'permissions' => [
                'description' => 'Array of permission names to assign to the role.',
                'example' => ['user.view', 'user.edit'],
            ],
        ];
    }
}
