<?php

namespace App\Actions\Category;

use App\Http\Requests\Category\CategorySearchRequest;
use App\Services\CategoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchCategoryAction
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function execute(CategorySearchRequest $request): LengthAwarePaginator
    {
        return $this->categoryService->getCategories($request->validatedFilters());
    }
}
