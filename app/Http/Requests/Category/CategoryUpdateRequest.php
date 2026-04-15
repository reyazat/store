<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->getKey() ?? $this->route('categoryID');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:categories,name,$categoryId"],
            'slug' => ['sometimes', 'required', 'string', 'max:255', "unique:categories,slug,$categoryId"],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
