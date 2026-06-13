<?php

namespace App\Http\Requests\SiteFeature;

use Illuminate\Foundation\Http\FormRequest;

class SiteFeatureUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('is_active')) {
            $merge['is_active'] = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->has('sort_order')) {
            $merge['sort_order'] = (int) $this->sort_order;
        }

        if ($this->has('icon') && $this->icon !== null && $this->icon !== '') {
            $merge['icon'] = $this->normalizeIcon($this->icon);
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
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

    /**
     * Normalize icon name to PascalCase (e.g. 'rotate-ccw' → 'RotateCcw', 'truck' → 'Truck').
     */
    private function normalizeIcon(string $icon): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $icon)));
    }
}
