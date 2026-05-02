<?php

namespace App\Actions\SiteSetting;

use App\Http\Requests\SiteSetting\SiteSettingUpdateRequest;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;

class SiteSettingUpdateAction
{
    public function __construct(
        private readonly SiteSettingRepositoryInterface $repository
    ) {}

    public function execute(SiteSettingUpdateRequest $request): array
    {
        $this->repository->updateMany($request->validated()['settings']);

        return $this->repository->getAll();
    }
}
