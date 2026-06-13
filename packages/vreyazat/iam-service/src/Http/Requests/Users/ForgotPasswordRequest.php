<?php

namespace IamApi\Http\Requests\Users;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends FormRequest
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
            // Channel is now optional - we send to both email and phone by default
            'channel' => [
                'nullable',
                'string',
                new NoHtml(),
                Rule::in(['email', 'sms', 'both'])
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
        // Normalize phone number to E.164 format (+98XXXXXXXXX)
        if ($this->has('identifier') && !filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            $this->merge(['identifier' => $this->normalizePhone($this->identifier)]);
        }

        // Normalize email to lowercase
        if ($this->has('identifier') && filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            $this->merge(['identifier' => strtolower($this->identifier)]);
        }
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
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($cleaned, '+98') && strlen($cleaned) === 13) {
            return $cleaned;
        }

        $digits = ltrim($cleaned, '+');

        if (preg_match('/^989\d{9}$/', $digits)) {
            return '+' . $digits;
        }

        if (preg_match('/^09\d{9}$/', $digits)) {
            return '+98' . substr($digits, 1);
        }

        if (preg_match('/^9\d{9}$/', $digits)) {
            return '+98' . $digits;
        }

        return str_starts_with($cleaned, '+') ? $cleaned : '+' . $digits;
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'identifier' => [
                'description' => 'Email address or phone number for password reset',
                'example' => 'user@example.com',
            ],
            'channel' => [
                'description' => 'Communication channel to send reset code (email or sms)',
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
}
