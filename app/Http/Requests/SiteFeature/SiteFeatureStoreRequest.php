<?php

namespace App\Http\Requests\SiteFeature;

use Illuminate\Foundation\Http\FormRequest;

class SiteFeatureStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => filter_var($this->is_active ?? true, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => (int) ($this->sort_order ?? 0),
            'icon'       => $this->icon ? $this->normalizeIcon($this->icon) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
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
