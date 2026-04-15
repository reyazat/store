<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Services\CategoryService;

class DestroyCategoryAction
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function execute(Category $category): void
    {
        $this->categoryService->deleteCategory($category);
    }
}
