<?php

namespace IamApi\Http\Requests\Audit;

use IamApi\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class ListActivityLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_uuid' => ['sometimes', 'string', 'uuid', 'exists:users,uuid'],
            'log_name' => ['sometimes', 'string', 'max:255', new NoHtml()],
            'event' => ['sometimes', 'string', 'max:255', new NoHtml()],
            'ip' => ['sometimes', 'ip'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'subject_type' => ['sometimes', 'string', 'max:255', new NoHtml()],
            'subject_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_uuid.uuid' => 'Invalid user UUID format',
            'user_uuid.exists' => 'User not found',
            'ip.ip' => 'Invalid IP address format',
            'start_date.date' => 'Start date must be a valid date',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page cannot exceed 100',
        ];
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'user_uuid' => [
                'description' => 'Filter by user UUID',
                'example' => '550e8400-e29b-41d4-a716-446655440000',
            ],
            'log_name' => [
                'description' => 'Filter by log name/category',
                'example' => 'default',
            ],
            'event' => [
                'description' => 'Filter by event description',
                'example' => 'login',
            ],
            'ip' => [
                'description' => 'Filter by IP address',
                'example' => '192.168.1.1',
            ],
            'start_date' => [
                'description' => 'Filter by start date (YYYY-MM-DD)',
                'example' => '2025-01-01',
            ],
            'end_date' => [
                'description' => 'Filter by end date (YYYY-MM-DD)',
                'example' => '2025-12-31',
            ],
            'subject_type' => [
                'description' => 'Filter by subject type',
                'example' => 'App\\Models\\User',
            ],
            'subject_id' => [
                'description' => 'Filter by subject ID',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Number of items per page',
                'example' => 15,
            ],
        ];
    }
}
