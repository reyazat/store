<?php

namespace App\Http\Requests\SiteFeature;

use Illuminate\Foundation\Http\FormRequest;

class SiteFeatureUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
