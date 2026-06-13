<?php

namespace IamApi\Http\Requests\Auth;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array 
    {
        return [
            'email' => ['required', 'email', 'max:255', new NoHtml()],
            'password' => ['required', 'string'],
            'captcha' => ['nullable', 'string', new NoHtml()],
            'captcha_key' => ['nullable', 'string', new NoHtml()],
            'device_name' => ['nullable', 'string', 'max:255', new NoHtml()],
            'remember_me' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'آدرس ایمیل الزامی است',
            'email.email' => 'لطفاً یک آدرس ایمیل معتبر وارد کنید',
            'password.required' => 'رمز عبور الزامی است',
            'captcha.string' => 'فرمت کپچا نامعتبر است',
            'captcha_key.string' => 'فرمت کلید کپچا نامعتبر است',
            'device_name.max' => 'نام دستگاه نباید بیشتر از ۲۵۵ کاراکتر باشد'
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'The user\'s email address',
                'example' => 'user@example.com',
            ],
            'password' => [
                'description' => 'The user\'s password',
                'example' => 'SecurePassword123!',
            ],
            'captcha' => [
                'description' => 'CAPTCHA value (required after multiple failed attempts)',
                'example' => 'abc123',
            ],
            'captcha_key' => [
                'description' => 'CAPTCHA key received from captcha endpoint',
                'example' => 'key_here',
            ],
            'device_name' => [
                'description' => 'The name of the device being used',
                'example' => 'Chrome on Windows',
            ],
            'remember_me' => [
                'description' => 'Whether to extend the refresh token lifetime',
                'example' => true,
            ],
        ];
    }
}
