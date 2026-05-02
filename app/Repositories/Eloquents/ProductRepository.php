<?php



namespace App\Repositories\Eloquents;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;




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

        if (!empty($filters['manufacturer_ids']) && is_array($filters['manufacturer_ids'])) {
            $query->whereIn('manufacturer_id', $filters['manufacturer_ids']);
        }

        if (!empty($filters['manufacturer_slug'])) {
            $query->whereHas('manufacturer', function ($builder) use ($filters) {
                $builder->where('slug', $filters['manufacturer_slug']);
            });
        }

        if (!empty($filters['tax_class_id'])) {
            $query->where('tax_class_id', $filters['tax_class_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($builder) use ($filters) {
                $builder->where('categories.id', $filters['category_id']);
            });
        }

        if (!empty($filters['category_slug'])) {
            $query->whereHas('categories', function ($builder) use ($filters) {
                $builder->where('categories.slug', $filters['category_slug']);
            });
        }

        if (!empty($filters['has_discount'])) {
            $query->hasActiveDiscount();
        }

        // Price range filter (based on active listings base_price)
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $min = $filters['min_price'] ?? null;
            $max = $filters['max_price'] ?? null;

            $query->whereHas('listings', function ($builder) use ($min, $max) {
                $builder->where('status', 'active');
                if ($min !== null) {
                    $builder->where('base_price', '>=', $min);
                }
                if ($max !== null) {
                    $builder->where('base_price', '<=', $max);
                }
            });
        }

        // In-stock filter: at least one active listing variant with stock > 0
        if (!empty($filters['in_stock'])) {
            $query->whereHas('listings', function ($builder) {
                $builder->where('status', 'active')
                    ->whereHas('listingVariants', function ($vb) {
                        $vb->where('status', 1)->where('stock_quantity', '>', 0);
                    });
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? null;
        switch ($sort) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'popular':
                $query->orderByDesc('viewed');
                break;
            case 'name_asc':
                $query->orderBy('name');
                break;
            case 'name_desc':
                $query->orderByDesc('name');
                break;
            case 'discount':
                $query->orderByDiscount('desc');
                break;
            case 'price_asc':
            case 'price_desc':
                $direction = $sort === 'price_asc' ? 'asc' : 'desc';
                $query->select('products.*')
                    ->leftJoin('listings', function ($join) {
                        $join->on('listings.product_id', '=', 'products.id')
                            ->where('listings.status', '=', 'active');
                    })
                    ->orderBy('listings.base_price', $direction)
                    ->groupBy('products.id');
                break;
            default:
                // Legacy fallback for sort_by_discount
                if (!empty($filters['sort_by_discount'])) {
                    $query->orderByDiscount($filters['sort_by_discount'] === 'asc' ? 'asc' : 'desc');
                } else {
                    $query->orderByDesc('sorted')->orderByDesc('created_at');
                }
        }

        // Featured flag: prioritize products with sorted > 0
        if (!empty($filters['featured'])) {
            $query->where('sorted', '>', 0);
        }

        $perPage = $filters['per_page'] ?? $filters['limit'] ?? 15;

        return $query->paginate((int) $perPage);
    }


    public function findById(int $id) :?Product
    {
        return Product::query()->find($id);
    }

    public function autocomplete(string $query, int $limit = 5): Collection
    {
        return Product::query()
            ->where('status', 1)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('base_sku', 'like', "%{$query}%");
            })
            ->select(['id', 'name', 'slug', 'main_image', 'discount_percent', 'discount_start_at', 'discount_end_at'])
            ->with(['listings' => function ($q) {
                $q->where('status', 'active')
                    ->select(['id', 'product_id', 'base_price'])
                    ->limit(1);
            }])
            ->orderByDesc('sorted')
            ->orderByDesc('viewed')
            ->limit($limit)
            ->get();
    }
}
