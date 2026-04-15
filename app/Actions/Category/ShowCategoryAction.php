<?php

namespace App\Actions\Category;

use App\Models\Category;

class ShowCategoryAction
{
    public function execute(Category $category): Category
    {
        return $category->load(['parent', 'children']);
    }
}
