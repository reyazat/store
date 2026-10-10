<?php

namespace App\Http\Requests\Admin\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['boolean'],
            'label' => ['string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'config' => ['nullable', 'array'],
            'config.*' => ['nullable', 'string', 'max:500'],
            'success_order_status' => ['nullable', 'string', 'exists:order_statuses,key'],
            'failure_order_status' => ['nullable', 'string', 'exists:order_statuses,key'],
            'cancel_order_status' => ['nullable', 'string', 'exists:order_statuses,key'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $paymentMethod = $this->route('paymentMethod');
            $isActive = $this->boolean('is_active', $paymentMethod->is_active);

            // Only check config if trying to activate
            if (! $isActive) {
                return;
            }

            $config = $this->input('config') ?? $paymentMethod->config ?? [];

            // Validate required config based on driver
            match ($paymentMethod->driver) {
                'avreen' => $this->validateAvreenConfig($validator),
                'zarinpal' => $this->validateZarinpalConfig($validator, $config),
                'card_to_card' => $this->validateCardToCardConfig($validator, $config),
                default => null,
            };
        });
    }

    private function validateAvreenConfig(Validator $validator): void
    {
        foreach (['mid', 'terminal_id', 'password', 'private_key_path', 'center_public_key_path'] as $key) {
            if (empty(config("services.avreen.{$key}"))) {
                $validator->errors()->add('config', 'برای فعال کردن درگاه آورین، تنظیمات محیطی و کلیدهای IPG باید کامل باشند.');

                return;
            }
        }
    }

    private function validateZarinpalConfig(Validator $validator, array $config): void
    {
        if (empty($config['merchant_id'])) {
            $validator->errors()->add('config.merchant_id', 'برای فعال کردن درگاه زرین‌پال، وارد کردن Merchant ID الزامی است.');
        }
    }

    private function validateCardToCardConfig(Validator $validator, array $config): void
    {
        if (empty($config['card_number'])) {
            $validator->errors()->add('config.card_number', 'برای فعال کردن روش کارت به کارت، وارد کردن شماره کارت الزامی است.');
        }
        if (empty($config['card_holder'])) {
            $validator->errors()->add('config.card_holder', 'برای فعال کردن روش کارت به کارت، وارد کردن نام صاحب کارت الزامی است.');
        }
    }
}
