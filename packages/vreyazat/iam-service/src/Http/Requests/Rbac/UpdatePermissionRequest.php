<?php

namespace IamApi\Http\Requests\Rbac;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IamApi\Rules\NoHtml;

class UpdatePermissionRequest extends FormRequest
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
        
        // Get the permission model from route (Spatie Permission uses id, not uuid)
        $permission = $this->route('permission');
        $permissionId = $permission instanceof \Spatie\Permission\Models\Permission ? $permission->id : $permission;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                new NoHtml(),
                Rule::unique('permissions', 'name')
                    ->where('organization_id', $organizationId)
                    ->where('guard_name', 'api')
                    ->ignore($permissionId),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A permission with this name already exists in this organization.',
        ];
    }

    /**
     * Get the body parameters for API documentation.
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The new name of the permission (optional). Should follow the pattern: resource.action.',
                'example' => 'article.publish-draft',
            ],
        ];
    }
}
