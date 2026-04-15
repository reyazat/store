<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;

class ListingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:active,inactive,pending'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'min_order_qty' => ['nullable', 'integer', 'min:1'],
            'variants' => ['nullable', 'array'],
            'variants.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.status' => ['nullable', 'string', 'in:active,inactive'],
            'customer_group_prices' => ['nullable', 'array'],
            'customer_group_prices.*.customer_group_id' => ['required', 'integer', 'exists:customer_groups,id'],
            'customer_group_prices.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
