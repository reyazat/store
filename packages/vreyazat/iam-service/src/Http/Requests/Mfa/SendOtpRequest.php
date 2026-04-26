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
                        $fail('The ' . $attribute . ' must be a valid email address or phone number.');
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
            'identifier.required' => 'Email address or phone number is required.',
            'type.required' => 'OTP type is required.',
            'type.in' => 'Invalid OTP type. Must be one of: login_verification, password_reset, email_verification, phone_verification, account_activation, security_verification.',
            'channel.required' => 'Communication channel is required.',
            'channel.in' => 'Invalid channel. Must be either email or sms.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'identifier' => 'email or phone number',
            'type' => 'OTP type',
            'channel' => 'communication channel',
            'captcha' => 'CAPTCHA value',
            'captcha_key' => 'CAPTCHA key',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone number format
        if ($this->has('identifier') && !filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            $phone = preg_replace('/[^\d+]/', '', $this->identifier);
            if (!str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }
            $this->merge(['identifier' => $phone]);
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
}
