<?php

namespace App\Services;

use App\Models\Slider;
use App\Repositories\Contracts\SliderRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SliderService
{
    public function __construct(
        private readonly SliderRepositoryInterface $sliderRepository
    ) {}

    public function storeImage(UploadedFile $file): string
    {
        return $file->store('sliders', 'public');
    }

    public function deleteImage(string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function create(array $data, ?UploadedFile $image = null): Slider
    {
        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $this->sliderRepository->getAll()->count();
        }

        return $this->sliderRepository->create($data);
    }

    public function update(Slider $slider, array $data, ?UploadedFile $image = null): Slider
    {
        if ($image) {
            $this->deleteImage($slider->image);
            $data['image'] = $this->storeImage($image);
        }

        return $this->sliderRepository->update($slider, $data);
    }

    public function delete(Slider $slider): void
    {
        $this->deleteImage($slider->image);
        $this->sliderRepository->delete($slider);
    }
}
