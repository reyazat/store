<?php

namespace App\Actions\SiteFeature;

use App\Http\Requests\SiteFeature\SiteFeatureStoreRequest;
use App\Models\SiteFeature;
use App\Repositories\Contracts\SiteFeatureRepositoryInterface;

class SiteFeatureStoreAction
{
    public function __construct(
        private readonly SiteFeatureRepositoryInterface $repository
    ) {}

    public function execute(SiteFeatureStoreRequest $request): SiteFeature
    {
        return $this->repository->create($request->validated());
    }
}
