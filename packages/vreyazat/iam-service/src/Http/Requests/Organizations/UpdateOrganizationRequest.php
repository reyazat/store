<?php

namespace IamApi\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;
use IamApi\Rules\NoHtml;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $org = $this->route('org');
        $orgId = $org instanceof \IamApi\Models\Organization ? $org->id : null;

        return [
            'name'        => ['sometimes', 'required', 'string', 'max:255', 'unique:organizations,name,' . $orgId, new NoHtml()],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo'        => ['nullable', 'string', 'max:500'],
            'status'      => ['nullable', 'in:active,inactive,suspended'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Organization name is required',
            'name.string'   => 'Organization name must be a string',
            'name.max'      => 'Organization name cannot exceed 255 characters',
            'name.unique'   => 'An organization with this name already exists',
            'status.in'     => 'Status must be one of: active, inactive, suspended',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name'        => ['description' => 'The new name of the organization', 'example' => 'Acme Corporation'],
            'description' => ['description' => 'Optional description',              'example' => 'A brief description'],
            'logo'        => ['description' => 'Optional logo URL or path',         'example' => 'logos/acme.png'],
            'status'      => ['description' => 'active|inactive|suspended',         'example' => 'active'],
        ];
    }
}
