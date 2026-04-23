<?php

namespace App\Actions\PromoBanner;

use App\Repositories\Contracts\PromoBannerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PromoBannerIndexAction
{
    public function __construct(
        private readonly PromoBannerRepositoryInterface $repository
    ) {}

    public function execute(bool $activeOnly = false, string $placement = 'home'): Collection
    {
        if ($activeOnly) {
            return $this->repository->getActiveByPlacement($placement);
        }

        return $this->repository->getAll($placement);
    }
}
