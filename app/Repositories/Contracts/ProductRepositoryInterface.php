<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Product;

/**
 * Interface ProductRepositoryInterface
 *
 * @package App\Repositories\Contracts
 */
interface ProductRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Product;
}