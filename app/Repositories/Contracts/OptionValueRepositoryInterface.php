<?php

namespace App\Repositories\Contracts;

use App\Models\OptionValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OptionValueRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?OptionValue;
}
