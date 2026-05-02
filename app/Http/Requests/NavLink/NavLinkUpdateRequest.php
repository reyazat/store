<?php

namespace App\Http\Requests\NavLink;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NavLinkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'      => ['sometimes', 'required', 'string', 'max:100'],
            'url'        => ['sometimes', 'required', 'string', 'max:500'],
            'group'      => ['sometimes', 'required', 'string', Rule::in(['header', 'footer_quick', 'footer_service'])],
            'target'     => ['nullable', 'string', Rule::in(['_self', '_blank'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'عنوان لینک الزامی است.',
            'url.required'   => 'آدرس لینک الزامی است.',
            'group.required' => 'گروه لینک الزامی است.',
            'group.in'       => 'گروه لینک باید یکی از مقادیر مجاز باشد.',
        ];
    }
}
