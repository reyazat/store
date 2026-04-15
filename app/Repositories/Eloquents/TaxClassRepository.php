<?php

namespace App\Repositories\Eloquents;

use App\Models\TaxClass;
use App\Repositories\Contracts\TaxClassRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaxClassRepository implements TaxClassRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = TaxClass::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate((int) $perPage);
    }

    public function findById(int $id): ?TaxClass
    {
        return TaxClass::query()->find($id);
    }
}
