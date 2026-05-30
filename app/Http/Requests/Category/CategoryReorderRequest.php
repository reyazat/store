<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:2'],
            'ids.*' => ['required', 'integer', 'exists:categories,id'],
        ];
    }

    public function orderedIds(): array
    {
        return $this->validated()['ids'];
    }
}
