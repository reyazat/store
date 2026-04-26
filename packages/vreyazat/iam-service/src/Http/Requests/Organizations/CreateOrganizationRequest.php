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
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name', new NoHtml()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Organization name is required',
            'name.string' => 'Organization name must be a string',
            'name.max' => 'Organization name cannot exceed 255 characters',
            'name.unique' => 'An organization with this name already exists',
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The name of the organization',
                'example' => 'Acme Corporation',
            ],
        ];
    }
}
