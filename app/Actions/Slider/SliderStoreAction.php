<?php

namespace App\Actions\Slider;

use App\Http\Requests\Slider\SliderStoreRequest;
use App\Models\Slider;
use App\Services\SliderService;

class SliderStoreAction
{
    public function __construct(
        private readonly SliderService $sliderService
    ) {}

    public function execute(SliderStoreRequest $request): Slider
    {
        $data = $request->validated();
        $image = $request->file('image');

        return $this->sliderService->create($data, $image);
    }
}
