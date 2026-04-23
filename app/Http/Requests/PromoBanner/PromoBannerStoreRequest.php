<?php

namespace App\Http\Requests\PromoBanner;

use Illuminate\Foundation\Http\FormRequest;

class PromoBannerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'subtitle'      => ['nullable', 'string', 'max:500'],
            'icon'          => ['nullable', 'string', 'max:100'],
            'image'         => ['nullable', 'image', 'max:4096'],
            'link'          => ['nullable', 'string', 'max:500'],
            'cta_text'      => ['nullable', 'string', 'max:100'],
            'gradient_from' => ['nullable', 'string', 'max:50'],
            'gradient_to'   => ['nullable', 'string', 'max:50'],
            'placement'     => ['nullable', 'string', 'in:home,sidebar,category,product'],
            'sort_order'    => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active'     => ['nullable', 'boolean'],
            'starts_at'     => ['nullable', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
