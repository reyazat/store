<?php

namespace IamApi\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;
use IamApi\Rules\NoHtml;

class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255', 'unique:organizations,name', new NoHtml()],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo'        => ['nullable', 'string', 'max:500'],
            'status'      => ['nullable', 'in:active,inactive,suspended'],
            'owner_id'    => ['nullable', 'integer', 'exists:users,id'],
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
            'owner_id.exists' => 'The specified owner user does not exist',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name'        => ['description' => 'The name of the organization',   'example' => 'Acme Corporation'],
            'description' => ['description' => 'Optional description',            'example' => 'A brief description'],
            'logo'        => ['description' => 'Optional logo URL or path',       'example' => 'logos/acme.png'],
            'status'      => ['description' => 'active|inactive|suspended',       'example' => 'active'],
            'owner_id'    => ['description' => 'Optional user ID to assign owner','example' => 5],
        ];
    }
}
