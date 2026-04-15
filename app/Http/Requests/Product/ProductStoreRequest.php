<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'base_sku' => ['required', 'string', 'max:255', 'unique:products,base_sku'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'main_image' => ['required', 'string', 'max:255'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'tax_class_id' => ['required', 'integer', 'exists:tax_classes,id'],
            'weight' => ['required', 'numeric', 'min:0'],
            'length' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
            'date_available' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'sorted' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'options' => ['nullable', 'array'],
            'options.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'options.*.required' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*.file_name' => ['required', 'string', 'max:255'],
            'images.*.sorted' => ['nullable', 'integer', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.length' => ['nullable', 'numeric', 'min:0'],
            'variants.*.width' => ['nullable', 'numeric', 'min:0'],
            'variants.*.height' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['nullable', 'integer', 'in:0,1'],
            'variants.*.option_values' => ['nullable', 'array'],
            'variants.*.option_values.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'variants.*.option_values.*.option_value_id' => ['required', 'integer', 'exists:option_values,id'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'discount_start_at' => ['nullable', 'date'],
            'discount_end_at' => ['nullable', 'date', 'after_or_equal:discount_start_at'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
