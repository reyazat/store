<?php

namespace App\Actions\Category;

use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Throwable;

class UpdateCategoryAction
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(CategoryUpdateRequest $request, Category $category): Category
    {
        $category = $this->categoryService->updateCategory($category, $request->validatedData());

        return $category->load(['parent', 'children']);
    }
}
