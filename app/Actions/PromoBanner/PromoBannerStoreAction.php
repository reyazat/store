<?php

namespace App\Actions\PromoBanner;

use App\Http\Requests\PromoBanner\PromoBannerStoreRequest;
use App\Models\PromoBanner;
use App\Services\PromoBannerService;

class PromoBannerStoreAction
{
    public function __construct(
        private readonly PromoBannerService $service
    ) {}

    public function execute(PromoBannerStoreRequest $request): PromoBanner
    {
        return $this->service->create($request->validated(), $request->file('image'));
    }
}
