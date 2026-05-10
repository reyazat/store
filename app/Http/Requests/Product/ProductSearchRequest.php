<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'manufacturer_ids' => ['nullable', 'array'],
            'manufacturer_ids.*' => ['integer', 'exists:manufacturers,id'],
            'tax_class_id' => ['nullable', 'integer', 'exists:tax_classes,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'manufacturer_slug' => ['nullable', 'string', 'exists:manufacturers,slug'],
            'featured' => ['nullable', 'in:true,false,1,0'],
            'special_offer' => ['nullable', 'in:true,false,1,0'],
            'has_discount' => ['nullable', 'in:true,false,1,0'],
            'sort_by_discount' => ['nullable', 'string', 'in:asc,desc'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'in_stock' => ['nullable', 'in:true,false,1,0'],
            'sort' => ['nullable', 'string', 'in:newest,oldest,price_asc,price_desc,popular,name_asc,name_desc,discount'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function validatedFilters(): array
    {
        $filters = $this->validated();

        // Treat `limit` as alias for `per_page` when provided
        if (!empty($filters['limit']) && empty($filters['per_page'])) {
            $filters['per_page'] = $filters['limit'];
        }

        // Normalize boolean string values to actual booleans
        $booleanFields = ['featured', 'special_offer', 'has_discount', 'in_stock'];
        foreach ($booleanFields as $field) {
            if (isset($filters[$field])) {
                $filters[$field] = in_array($filters[$field], [true, 'true', 1, '1'], true);
            }
        }

        return $filters;
    }
}
