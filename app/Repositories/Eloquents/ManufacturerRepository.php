<?php

namespace App\Repositories\Eloquents;

use App\Models\Manufacturer;
use App\Repositories\Contracts\ManufacturerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Manufacturer::query();

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

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate((int) $perPage);
    }

    public function findById(int $id): ?Manufacturer
    {
        return Manufacturer::query()->find($id);
    }
}
