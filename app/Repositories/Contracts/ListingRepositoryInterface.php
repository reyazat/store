<?php

namespace App\Repositories\Contracts;

use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ListingRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Listing;

    public function findByProductAndVendor(int $productId, int $vendorId): ?Listing;
}
