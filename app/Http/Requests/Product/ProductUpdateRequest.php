<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->getKey() ?? $this->route('productID');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', "unique:products,slug,$productId"],
            'base_sku' => ['sometimes', 'required', 'string', 'max:255', "unique:products,base_sku,$productId"],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'main_image'      => ['nullable', 'string', 'max:255'],
            'main_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'tax_class_id' => ['sometimes', 'required', 'integer', 'exists:tax_classes,id'],
            'weight' => ['sometimes', 'required', 'numeric', 'min:0'],
            'length' => ['sometimes', 'required', 'numeric', 'min:0'],
            'width' => ['sometimes', 'required', 'numeric', 'min:0'],
            'height' => ['sometimes', 'required', 'numeric', 'min:0'],
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
            'images'             => ['nullable', 'array'],
            'images.*.file'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images.*.file_name' => ['nullable', 'string', 'max:255'],
            'images.*.sorted'    => ['nullable', 'integer', 'min:0'],
            'related_product_ids'   => ['nullable', 'array'],
            'related_product_ids.*' => ['integer', 'exists:products,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.length' => ['nullable', 'numeric', 'min:0'],
            'variants.*.width' => ['nullable', 'numeric', 'min:0'],
            'variants.*.height' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['nullable', 'integer', 'in:0,1'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
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
