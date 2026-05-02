<?php

namespace App\Http\Requests\SiteSetting;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingUploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key'  => ['required', 'string', 'in:logo,favicon'],
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,svg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.in'        => 'کلید فایل معتبر نیست.',
            'file.required' => 'فایلی انتخاب نشده است.',
            'file.image'    => 'فایل باید یک تصویر باشد.',
            'file.max'      => 'حداکثر حجم فایل ۲ مگابایت است.',
        ];
    }
}
