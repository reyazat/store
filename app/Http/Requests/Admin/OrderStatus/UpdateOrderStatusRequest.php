<?php

namespace App\Http\Requests\Admin\OrderStatus;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'                => ['string', 'max:100'],
            'color'                => ['string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_cancellation'      => ['boolean'],
            'is_completion'        => ['boolean'],
            'is_shipping'          => ['boolean'],
            'sort_order'           => ['integer', 'min:0'],
            'notify_customer'      => ['boolean'],
            'notification_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
