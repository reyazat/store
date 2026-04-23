<?php

namespace App\Actions\PromoBanner;

use App\Http\Requests\PromoBanner\PromoBannerUpdateRequest;
use App\Models\PromoBanner;
use App\Services\PromoBannerService;

class PromoBannerUpdateAction
{
    public function __construct(
        private readonly PromoBannerService $service
    ) {}

    public function execute(PromoBannerUpdateRequest $request, PromoBanner $banner): PromoBanner
    {
        return $this->service->update($banner, $request->validated(), $request->file('image'));
    }
}
