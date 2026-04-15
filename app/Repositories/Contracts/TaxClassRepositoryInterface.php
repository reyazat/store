<?php

namespace App\Repositories\Contracts;

use App\Models\TaxClass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaxClassRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?TaxClass;
}
