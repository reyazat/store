<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderSetShippingCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_code' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_code.required' => 'کد رهگیری الزامی است.',
            'shipping_code.max'      => 'کد رهگیری نباید بیش از ۱۰۰ کاراکتر باشد.',
        ];
    }
}
