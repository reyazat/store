<?php

namespace App\Repositories\Contracts;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VendorRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Vendor;
}
