<?php

namespace App\Actions\SiteFeature;

use App\Repositories\Contracts\SiteFeatureRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SiteFeatureIndexAction
{
    public function __construct(
        private readonly SiteFeatureRepositoryInterface $repository
    ) {}

    public function execute(bool $activeOnly = false): Collection
    {
        return $activeOnly
            ? $this->repository->getActive()
            : $this->repository->getAll();
    }
}
