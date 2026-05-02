<?php

namespace App\Repositories\Eloquents;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query()->withCount('products');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('parent_id', $filters) && $filters['parent_id'] !== null) {
            $query->where('parent_id', $filters['parent_id']);
        }

        $perPage = $filters['per_page'] ?? 20;

        return $query->orderBy('sorted')->orderBy('id')->paginate((int) $perPage);
    }

    public function findById(int $id): ?Category
    {
        return Category::query()->find($id);
    }

    public function getHeaderCategories(int $limit = 6): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('status', 1)
            ->withCount(['products' => function ($query) {
                $query->where('status', 1);
            }])
            ->orderByDesc('products_count')
            ->orderBy('sorted')
            ->limit($limit)
            ->get();
    }
}
