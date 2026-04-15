<?php



namespace App\Repositories\Eloquents;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;




class ProductRepository implements ProductRepositoryInterface
{


    public function getAll(array $filters = []) :LengthAwarePaginator
    {
        $query = Product::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%$search%")
                    ->orWhere('slug', 'like', "%$search%")
                    ->orWhere('base_sku', 'like', "%$search%");
            });
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['manufacturer_id'])) {
            $query->where('manufacturer_id', $filters['manufacturer_id']);
        }

        if (!empty($filters['tax_class_id'])) {
            $query->where('tax_class_id', $filters['tax_class_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($builder) use ($filters) {
                $builder->where('categories.id', $filters['category_id']);
            });
        }

        if (!empty($filters['has_discount'])) {
            $query->hasActiveDiscount();
        }

        if (!empty($filters['sort_by_discount'])) {
            $query->orderByDiscount($filters['sort_by_discount'] === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate((int) $perPage);
    }


    public function findById(int $id) :?Product
    {
        return Product::query()->find($id);
    }
}
