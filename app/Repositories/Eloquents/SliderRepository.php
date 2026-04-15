<?php

namespace App\Repositories\Eloquents;

use App\Models\Slider;
use App\Repositories\Contracts\SliderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SliderRepository implements SliderRepositoryInterface
{
    public function getActive(): Collection
    {
        return Slider::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getAll(): Collection
    {
        return Slider::orderBy('sort_order')->get();
    }

    public function findById(int $id): ?Slider
    {
        return Slider::find($id);
    }

    public function create(array $data): Slider
    {
        return Slider::create($data);
    }

    public function update(Slider $slider, array $data): Slider
    {
        $slider->update($data);
        return $slider->fresh();
    }

    public function delete(Slider $slider): void
    {
        $slider->delete();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Slider::where('id', $id)->update(['sort_order' => $index]);
        }
    }
}
