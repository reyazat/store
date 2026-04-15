<?php

namespace App\Repositories\Contracts;

use App\Models\CustomerGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerGroupRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?CustomerGroup;
}
