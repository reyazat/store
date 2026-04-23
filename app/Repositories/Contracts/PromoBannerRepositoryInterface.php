<?php

namespace App\Repositories\Contracts;

use App\Models\PromoBanner;
use Illuminate\Database\Eloquent\Collection;

interface PromoBannerRepositoryInterface
{
    public function getActiveByPlacement(string $placement): Collection;

    public function getAll(?string $placement = null): Collection;

    public function findById(int $id): ?PromoBanner;

    public function create(array $data): PromoBanner;

    public function update(PromoBanner $banner, array $data): PromoBanner;

    public function delete(PromoBanner $banner): void;
}
