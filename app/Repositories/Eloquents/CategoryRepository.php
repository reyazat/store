<?php

namespace App\Repositories\Eloquents;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query()->withCount(['products', 'children']);

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
            if ($filters['parent_id'] === 0 || $filters['parent_id'] === '0') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        $perPage = $filters['per_page'] ?? 20;

        return $query->orderBy('sorted')->orderBy('id')->paginate((int) $perPage);
    }

    public function findById(int $id): ?Category
    {
        return Category::query()->find($id);
    }

    
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Category::where('id', $id)->update(['sorted' => $index]);
        }
    }

    public function getTopSubcategories(int $limit = 10): Collection
    {
        return Category::query()
            ->whereNotNull('parent_id') // Only subcategories
            ->where('status', 1)
            ->withCount(['products' => function ($query) {
                $query->where('status', 1);
            }])
            ->with(['parent' => function ($query) {
                $query->select('id', 'name', 'slug');
            }])
            ->addSelect(['total_sales' => function ($query) {
                $query->selectRaw('COALESCE(SUM(oi.quantity), 0)')
                    ->from('order_items as oi')
                    ->join('orders as o', 'oi.order_id', '=', 'o.id')
                    ->join('products as p', 'oi.product_id', '=', 'p.id')
                    ->join('category_product as cp', 'p.id', '=', 'cp.product_id')
                    ->where('cp.category_id', '=', DB::raw('categories.id'))
                    ->where('o.status', 'completed');
            }])
            ->orderByDesc('products_count')
            ->orderByDesc('total_sales')
            ->take($limit)
            ->get();
    }
}
