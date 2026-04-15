<?php

namespace App\Actions\Slider;

use App\Repositories\Contracts\SliderRepositoryInterface;

class SliderReorderAction
{
    public function __construct(
        private readonly SliderRepositoryInterface $sliderRepository
    ) {}

    public function execute(array $orderedIds): void
    {
        $this->sliderRepository->reorder($orderedIds);
    }
}
