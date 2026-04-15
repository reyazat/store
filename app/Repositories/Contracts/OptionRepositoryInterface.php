<?php

namespace App\Repositories\Contracts;

use App\Models\Option;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OptionRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Option;
}
