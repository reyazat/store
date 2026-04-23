<?php

namespace App\Actions\SiteFeature;

use App\Http\Requests\SiteFeature\SiteFeatureUpdateRequest;
use App\Models\SiteFeature;
use App\Repositories\Contracts\SiteFeatureRepositoryInterface;

class SiteFeatureUpdateAction
{
    public function __construct(
        private readonly SiteFeatureRepositoryInterface $repository
    ) {}

    public function execute(SiteFeatureUpdateRequest $request, SiteFeature $feature): SiteFeature
    {
        return $this->repository->update($feature, $request->validated());
    }
}
