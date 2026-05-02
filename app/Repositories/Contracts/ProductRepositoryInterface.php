<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface ProductRepositoryInterface
 *
 * @package App\Repositories\Contracts
 */
interface ProductRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Product;

    public function autocomplete(string $query, int $limit = 5): Collection;
}