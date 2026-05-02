<?php

namespace App\Actions\Product;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProductAutocompleteAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function execute(string $query, int $limit = 5): Collection
    {
        return $this->repository->autocomplete($query, $limit);
    }
}
