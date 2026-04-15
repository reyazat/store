<?php

namespace App\Repositories\Eloquents;

use App\Models\OptionValue;
use App\Repositories\Contracts\OptionValueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OptionValueRepository implements OptionValueRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = OptionValue::query();

        if (!empty($filters['option_id'])) {
            $query->where('option_id', $filters['option_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('value', 'like', "%{$search}%");
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('sorted')->paginate((int) $perPage);
    }

    public function findById(int $id): ?OptionValue
    {
        return OptionValue::query()->find($id);
    }
}
