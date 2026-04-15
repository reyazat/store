<?php

namespace App\Actions\Category;

use App\Http\Requests\Category\CategoryStoreRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Throwable;

class StoreCategoryAction
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(CategoryStoreRequest $request): Category
    {
        return $this->categoryService->createCategory($request->validatedData())->load(['parent', 'children']);
    }
}
