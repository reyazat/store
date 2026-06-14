<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryImageService        $imageService,
    ) {}

    public function getCategories(array $filters = []): LengthAwarePaginator
    {
        return $this->categoryRepository->getAll($filters);
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    
    public function getHierarchicalCategories(): array
    {
        $categories = Category::query()
        ->with(['children' => function ($query) {
            $query->where('status', 1)->orderBy('name');
        }])
        ->whereNull('parent_id')
        ->where('status', 1)
        ->withCount('children')
        ->orderByDesc('children_count')
        ->orderBy('name')
        ->get();

        return $categories->map(function ($category) {
            $hasSubcategories = $category->children->count() > 0;
            
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image' => $category->image,
                'icon' => $category->icon,
                'children_count' => $category->children_count,
                'direct_products_count' => $hasSubcategories ? 0 : $category->products()->count(),
                'children' => $category->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                        'image' => $child->image,
                        'products_count' => $child->products()->count(),
                    ];
                }),
            ];
        })->toArray();
    }

    public function getTopSubcategories(int $limit = 10): Collection
    {
        return $this->categoryRepository->getTopSubcategories($limit);
    }

    /**
     * @throws Throwable
     */
    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $data = $this->resolveImage($data);

            return Category::query()->create($this->categoryAttributes($data));
        });
    }

    /**
     * @throws Throwable
     */
    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            if (!empty($data['image_file'])) {
                $this->imageService->delete($category->image);
            }

            $data = $this->resolveImage($data);
            $category->update($this->categoryAttributes($data));

            return $category->refresh();
        });
    }

    /**
     * Delete a category with checks for children and products.
     * Prevents deletion if category has subcategories or is used in products.
     *
     * @throws \RuntimeException if category has children or is used in products
     */
    public function deleteCategory(Category $category): void
    {
        // Check if category has children (subcategories)
        $childrenCount = $category->children()->count();
        if ($childrenCount > 0) {
            throw new \RuntimeException(
                sprintf(
                    'این دسته‌بندی دارای %d زیردسته است و قابل حذف نیست. ابتدا زیردسته‌ها را حذف کنید.',
                    $childrenCount
                )
            );
        }

        // Check if category is used in any products
        $productCount = $category->products()->count();
        if ($productCount > 0) {
            throw new \RuntimeException(
                sprintf(
                    'این دسته‌بندی در %d محصول استفاده شده است و قابل حذف نیست.',
                    $productCount
                )
            );
        }

        // Delete image if exists
        $this->imageService->delete($category->image);

        // Soft delete the category
        // DB cascade will handle category_product (onDelete cascade)
        // DB nullOnDelete handles children.parent_id (set to null)
        $category->delete();
    }

    private function resolveImage(array $data): array
    {
        if (!empty($data['image_file'])) {
            $data['image'] = $this->imageService->store($data['image_file']);
        }

        return $data;
    }

    private function categoryAttributes(array $data): array
    {
        return Arr::only($data, [
            'parent_id',
            'name',
            'slug',
            'description',
            'image',
            'icon',
            'meta_title',
            'meta_description',
            'status',
            'sorted',
        ]);
    }
}
