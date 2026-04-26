<?php

namespace IamApi\Http\Requests\Mfa;

use IamApi\Rules\NoHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
                new NoHtml()
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
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Email address or phone number is required.',
            'code.required' => 'OTP code is required.',
            'code.size' => 'OTP code must be exactly 6 digits.',
            'code.regex' => 'OTP code must contain only numbers.',
            'type.required' => 'OTP type is required.',
            'type.in' => 'Invalid OTP type. Must be one of: login_verification, password_reset, email_verification, phone_verification, account_activation, security_verification.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'identifier' => 'email or phone number',
            'code' => 'OTP code',
            'type' => 'OTP type',
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

        // Remove any spaces or special characters from code
        if ($this->has('code')) {
            $this->merge(['code' => preg_replace('/[^\d]/', '', $this->code)]);
        }
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'identifier' => [
                'description' => 'Email address or phone number used to send the OTP',
                'example' => 'user@example.com',
            ],
            'code' => [
                'description' => 'The 6-digit OTP code received',
                'example' => '123456',
            ],
            'type' => [
                'description' => 'Type of OTP verification (login_verification, password_reset, email_verification, phone_verification, account_activation, security_verification)',
                'example' => 'login_verification',
            ],
        ];
    }
}
