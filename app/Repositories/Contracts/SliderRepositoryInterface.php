<?php

namespace App\Repositories\Contracts;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Collection;

interface SliderRepositoryInterface
{
    public function getActive(): Collection;

    public function getAll(): Collection;

    public function findById(int $id): ?Slider;

    public function create(array $data): Slider;

    public function update(Slider $slider, array $data): Slider;

    public function delete(Slider $slider): void;

    public function reorder(array $orderedIds): void;
}
