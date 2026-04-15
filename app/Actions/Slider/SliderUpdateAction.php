<?php

namespace App\Actions\Slider;

use App\Http\Requests\Slider\SliderUpdateRequest;
use App\Models\Slider;
use App\Services\SliderService;

class SliderUpdateAction
{
    public function __construct(
        private readonly SliderService $sliderService
    ) {}

    public function execute(SliderUpdateRequest $request, Slider $slider): Slider
    {
        $data = $request->validated();
        $image = $request->file('image');

        return $this->sliderService->update($slider, $data, $image);
    }
}
