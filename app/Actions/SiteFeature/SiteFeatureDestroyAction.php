<?php

namespace App\Actions\SiteFeature;

use App\Models\SiteFeature;
use App\Repositories\Contracts\SiteFeatureRepositoryInterface;

class SiteFeatureDestroyAction
{
    public function __construct(
        private readonly SiteFeatureRepositoryInterface $repository
    ) {}

    public function execute(SiteFeature $feature): void
    {
        $this->repository->delete($feature);
    }
}
