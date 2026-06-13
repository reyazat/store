<?php

namespace IamApi\Http\Requests\Mfa;

use IamApi\Rules\NoHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => [
                'required',
                'string',
                'max:255',
                new NoHtml(),
                function ($attribute, $value, $fail) {
                    // Validate email or phone format
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL) && !preg_match('/^\+?[1-9]\d{1,14}$/', $value)) {
                        $fail('فرمت ایمیل یا شماره موبایل نامعتبر است.');
                    }
                },
            ],
            'type' => [
                'required',
                'string',
                new NoHtml(),
                Rule::in([
                    'login_verification',
                    'password_reset',
                    'email_verification',
                    'phone_verification',
                    'account_activation',
                    'security_verification'
                ])
            ],
            'channel' => [
                'required',
                'string',
                new NoHtml(),
                Rule::in(['email', 'sms'])
            ],
            'captcha' => [
                'nullable',
                'string',
                'max:255',
                new NoHtml()
            ],
            'captcha_key' => [
                'nullable',
                'string',
                'max:255',
                new NoHtml()
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'آدرس ایمیل یا شماره موبایل الزامی است.',
            'type.required' => 'نوع کد یکبار مصرف الزامی است.',
            'type.in' => 'نوع کد یکبار مصرف نامعتبر است.',
            'channel.required' => 'کانال ارسال الزامی است.',
            'channel.in' => 'کانال ارسال نامعتبر است. باید ایمیل یا پیامک باشد.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'identifier' => 'ایمیل یا شماره موبایل',
            'type' => 'نوع کد یکبار مصرف',
            'channel' => 'کانال ارسال',
            'captcha' => 'مقدار کپچا',
            'captcha_key' => 'کلید کپچا',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone number format
        if ($this->has('identifier') && !filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            $this->merge(['identifier' => $this->normalizePhone($this->identifier)]);
        }

        // Normalize email to lowercase
        if ($this->has('identifier') && filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            $this->merge(['identifier' => strtolower($this->identifier)]);
        }
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'identifier' => [
                'description' => 'Email address or phone number to send OTP to',
                'example' => 'user@example.com',
            ],
            'type' => [
                'description' => 'Type of OTP (login_verification, password_reset, email_verification, phone_verification, account_activation, security_verification)',
                'example' => 'login_verification',
            ],
            'channel' => [
                'description' => 'Communication channel (email or sms)',
                'example' => 'email',
            ],
            'captcha' => [
                'description' => 'CAPTCHA value for verification',
                'example' => 'abc123',
            ],
            'captcha_key' => [
                'description' => 'CAPTCHA key received from captcha endpoint',
                'example' => 'key_here',
            ],
        ];
    }

    /**
     * Normalize an Iranian phone number to E.164 format (+98XXXXXXXXX).
     *
     * Handles all common user inputs:
     *   09123456789  → +989123456789
     *   9123456789   → +989123456789
     *   989123456789 → +989123456789
     *  +989123456789 → +989123456789  (already correct)
     */
    private function normalizePhone(string $phone): string
    {
        // Strip everything except digits and a leading +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Already in E.164 format
        if (str_starts_with($cleaned, '+98') && strlen($cleaned) === 13) {
            return $cleaned;
        }

        // Strip any leading + so we can work with digits only
        $digits = ltrim($cleaned, '+');

        // 989XXXXXXXXX (12 digits, starts with 98)
        if (preg_match('/^989\d{9}$/', $digits)) {
            return '+' . $digits;
        }

        // 09XXXXXXXXX (11 digits, starts with 09)
        if (preg_match('/^09\d{9}$/', $digits)) {
            return '+98' . substr($digits, 1);
        }

        // 9XXXXXXXXX (10 digits, starts with 9)
        if (preg_match('/^9\d{9}$/', $digits)) {
            return '+98' . $digits;
        }

        // Non-Iranian number or already has country code — add + if missing
        return str_starts_with($cleaned, '+') ? $cleaned : '+' . $digits;
    }
}
