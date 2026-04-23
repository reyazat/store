<?php

namespace App\Actions\PromoBanner;

use App\Models\PromoBanner;
use App\Services\PromoBannerService;

class PromoBannerDestroyAction
{
    public function __construct(
        private readonly PromoBannerService $service
    ) {}

    public function execute(PromoBanner $banner): void
    {
        $this->service->delete($banner);
    }
}
