<?php

namespace App\Actions\SiteSetting;

use App\Repositories\Contracts\SiteSettingRepositoryInterface;

class SiteSettingIndexAction
{
    public function __construct(
        private readonly SiteSettingRepositoryInterface $repository
    ) {}

    public function execute(): array
    {
        return $this->repository->getAll();
    }
}
