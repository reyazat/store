<?php

namespace App\Repositories\Eloquents;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorRepository implements VendorRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Vendor::query();

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

        if (array_key_exists('approval_status', $filters) && $filters['approval_status'] !== null) {
            $query->where('approval_status', $filters['approval_status']);
        }

        if (array_key_exists('is_platform', $filters) && $filters['is_platform'] !== null) {
            $query->where('is_platform', $filters['is_platform']);
        }

        if (!empty($filters['sort_by'])) {
            $direction = $filters['sort_dir'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $direction);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate((int) $perPage);
    }

    public function findById(int $id): ?Vendor
    {
        return Vendor::query()->find($id);
    }
}
