<?php

namespace App\Actions\Category;

use App\Repositories\Contracts\CategoryRepositoryInterface;

class ReorderCategoriesAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(array $orderedIds): void
    {
        $this->categoryRepository->reorder($orderedIds);
    }
}
