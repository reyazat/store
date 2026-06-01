<?php

namespace App\Services;

use App\Models\Slider;
use App\Repositories\Contracts\SliderRepositoryInterface;
use Illuminate\Http\UploadedFile;

class SliderService
{
    public function __construct(
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly ImageUploadService $imageUploadService,
    ) {}

    public function create(array $data, ?UploadedFile $image = null): Slider
    {
        if ($image) {
            $data['image'] = $this->imageUploadService->store($image, 'sliders');
        }

        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $this->sliderRepository->getAll()->count();
        }

        return $this->sliderRepository->create($data);
    }

    public function update(Slider $slider, array $data, ?UploadedFile $image = null): Slider
    {
        if ($image) {
            $this->imageUploadService->delete($slider->image);
            $data['image'] = $this->imageUploadService->store($image, 'sliders');
        }

        return $this->sliderRepository->update($slider, $data);
    }

    public function delete(Slider $slider): void
    {
        $this->imageUploadService->delete($slider->image);
        $this->sliderRepository->delete($slider);
    }
}
