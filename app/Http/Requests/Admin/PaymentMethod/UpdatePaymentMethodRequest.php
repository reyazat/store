<?php

namespace App\Http\Requests\Admin\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active'            => ['boolean'],
            'label'                => ['string', 'max:100'],
            'description'          => ['nullable', 'string', 'max:500'],
            'sort_order'           => ['integer', 'min:0'],
            'config'               => ['nullable', 'array'],
            'config.*'             => ['nullable', 'string', 'max:500'],
            'success_order_status' => ['nullable', 'string', 'exists:order_statuses,key'],
            'failure_order_status' => ['nullable', 'string', 'exists:order_statuses,key'],
            'cancel_order_status'  => ['nullable', 'string', 'exists:order_statuses,key'],
        ];
    }
}
