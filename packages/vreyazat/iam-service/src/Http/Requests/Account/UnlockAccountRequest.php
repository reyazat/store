<?php

namespace IamApi\Http\Requests\Account;

use IamApi\Rules\NoHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnlockAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'unlock_method' => [
                'required',
                'string',
                Rule::in(['otp', 'admin', 'security_question', 'time_based'])
            ],
            'verification_code' => [
                'required_if:unlock_method,otp',
                'nullable',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/'
            ],
            'admin_token' => [
                'required_if:unlock_method,admin',
                'nullable',
                'string',
                new NoHtml()
            ],
            'security_answer' => [
                'required_if:unlock_method,security_question',
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
            'unlock_method.required' => 'Unlock method is required.',
            'unlock_method.in' => 'Invalid unlock method. Must be one of: otp, admin, security_question, time_based.',
            'verification_code.required_if' => 'Verification code is required when using OTP method.',
            'verification_code.size' => 'Verification code must be exactly 6 digits.',
            'verification_code.regex' => 'Verification code must contain only numbers.',
            'admin_token.required_if' => 'Admin token is required when using admin method.',
            'security_answer.required_if' => 'Security answer is required when using security question method.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'unlock_method' => 'unlock method',
            'verification_code' => 'verification code',
            'admin_token' => 'admin token',
            'security_answer' => 'security answer',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean verification code
        if ($this->has('verification_code')) {
            $this->merge(['verification_code' => preg_replace('/[^\d]/', '', $this->verification_code)]);
        }

        // Trim security answer
        if ($this->has('security_answer')) {
            $this->merge(['security_answer' => trim($this->security_answer)]);
        }
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'unlock_method' => [
                'description' => 'Method to unlock the account (otp, admin, security_question, time_based)',
                'example' => 'otp',
            ],
            'verification_code' => [
                'description' => 'The 6-digit verification code (required when unlock_method is otp)',
                'example' => '123456',
            ],
            'admin_token' => [
                'description' => 'Admin authorization token (required when unlock_method is admin)',
                'example' => 'admin_token_here',
            ],
            'security_answer' => [
                'description' => 'Answer to security question (required when unlock_method is security_question)',
                'example' => 'My first pet name',
            ],
        ];
    }
}
