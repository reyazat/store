<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryService
{
    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
    }

    public function getCategories(array $filters = []): LengthAwarePaginator
    {
        return $this->categoryRepository->getAll($filters);
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            return Category::query()->create($this->categoryAttributes($data));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update($this->categoryAttributes($data));

            return $category->refresh();
        });
    }

    public function deleteCategory(Category $category): void
    {
        Category::query()->whereKey($category->getKey())->delete();
    }

    private function categoryAttributes(array $data): array
    {
        return Arr::only($data, [
            'parent_id',
            'name',
            'slug',
            'description',
            'image',
            'meta_title',
            'meta_description',
            'status',
        ]);
    }
}
