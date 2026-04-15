<?php

namespace App\Actions\Slider;

use App\Models\Slider;
use App\Services\SliderService;

class SliderDestroyAction
{
    public function __construct(
        private readonly SliderService $sliderService
    ) {}

    public function execute(Slider $slider): void
    {
        $this->sliderService->delete($slider);
    }
}
