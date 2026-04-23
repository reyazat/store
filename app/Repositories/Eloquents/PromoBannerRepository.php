<?php

namespace App\Repositories\Eloquents;

use App\Models\PromoBanner;
use App\Repositories\Contracts\PromoBannerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PromoBannerRepository implements PromoBannerRepositoryInterface
{
    public function getActiveByPlacement(string $placement): Collection
    {
        return PromoBanner::active()
            ->placement($placement)
            ->orderBy('sort_order')
            ->get();
    }

    public function getAll(?string $placement = null): Collection
    {
        $query = PromoBanner::query()->orderBy('sort_order');
        if ($placement) {
            $query->where('placement', $placement);
        }
        return $query->get();
    }

    public function findById(int $id): ?PromoBanner
    {
        return PromoBanner::find($id);
    }

    public function create(array $data): PromoBanner
    {
        return PromoBanner::create($data);
    }

    public function update(PromoBanner $banner, array $data): PromoBanner
    {
        $banner->update($data);
        return $banner->fresh();
    }

    public function delete(PromoBanner $banner): void
    {
        $banner->delete();
    }
}
