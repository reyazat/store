<?php

namespace App\Http\Requests\SiteSetting;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings'         => ['required', 'array'],
            'settings.*'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'تنظیمات الزامی است.',
            'settings.array'    => 'فرمت تنظیمات نادرست است.',
        ];
    }
}
