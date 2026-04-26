<?php

namespace IamApi\Http\Requests\Rbac;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use IamApi\Rules\NoHtml;

class CreatePermissionRequest extends FormRequest
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
                Rule::unique('permissions', 'name')
                    ->where('organization_id', $organizationId)
                    ->where('guard_name', 'api'),
            ],
            'guard_name' => 'sometimes|string|in:api',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Permission name is required.',
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
                'description' => 'The name of the permission. Should follow the pattern: resource.action (e.g., user.view, article.publish).',
                'example' => 'article.publish',
            ],
            'guard_name' => [
                'description' => 'The guard name for the permission. Defaults to "api".',
                'example' => 'api',
            ],
        ];
    }
}
