<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class CartAddItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
            'quantity'   => ['nullable', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ];
    }
}
