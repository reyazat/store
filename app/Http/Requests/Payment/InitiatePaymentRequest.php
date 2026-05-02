<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'driver'   => ['required', 'string', Rule::exists('payment_methods', 'driver')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'شناسه سفارش الزامی است',
            'driver.required'   => 'روش پرداخت الزامی است',
            'driver.exists'     => 'روش پرداخت انتخاب‌شده معتبر نیست',
        ];
    }
}
