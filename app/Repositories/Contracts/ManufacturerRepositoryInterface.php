<?php

namespace App\Repositories\Contracts;

use App\Models\Manufacturer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ManufacturerRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Manufacturer;
}
