<?php

namespace IamApi\Http\Requests\Rbac;

use IamApi\Rules\NoHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignPermissionsRequest extends FormRequest
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
            'permissions' => 'required|array|min:1',
            'permissions.*' => [
                'string',
                new NoHtml(),
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
            'permissions.required' => 'At least one permission is required.',
            'permissions.*.exists' => 'One or more permissions do not exist in this organization.',
        ];
    }

    /**
     * Get the body parameters for API documentation.
     */
    public function bodyParameters(): array
    {
        return [
            'permissions' => [
                'description' => 'Array of permission names to assign to the role.',
                'example' => ['user.create', 'user.delete'],
            ],
        ];
    }
}
