<?php

namespace App\Repositories\Eloquents;

use App\Models\Option;
use App\Repositories\Contracts\OptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OptionRepository implements OptionRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Option::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('sorted')->paginate((int) $perPage);
    }

    public function findById(int $id): ?Option
    {
        return Option::query()->find($id);
    }
}
