<?php

namespace App\Repositories\Eloquents;

use App\Models\CustomerGroup;
use App\Repositories\Contracts\CustomerGroupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerGroupRepository implements CustomerGroupRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = CustomerGroup::query();

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

        if (array_key_exists('approve_customer', $filters) && $filters['approve_customer'] !== null) {
            $query->where('approve_customer', $filters['approve_customer']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate((int) $perPage);
    }

    public function findById(int $id): ?CustomerGroup
    {
        return CustomerGroup::query()->find($id);
    }
}
