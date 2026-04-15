<?php

namespace App\Repositories\Eloquents;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query();

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

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate((int) $perPage);
    }

    public function findById(int $id): ?Category
    {
        return Category::query()->find($id);
    }
}
