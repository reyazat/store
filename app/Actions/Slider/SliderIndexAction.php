<?php

namespace App\Actions\Slider;

use App\Repositories\Contracts\SliderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SliderIndexAction
{
    public function __construct(
        private readonly SliderRepositoryInterface $sliderRepository
    ) {}

    public function execute(bool $activeOnly = false): Collection
    {
        return $activeOnly
            ? $this->sliderRepository->getActive()
            : $this->sliderRepository->getAll();
    }
}
