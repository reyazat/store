<?php

namespace App\Repositories\Eloquents;

use App\Models\SiteFeature;
use App\Repositories\Contracts\SiteFeatureRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SiteFeatureRepository implements SiteFeatureRepositoryInterface
{
    public function getActive(): Collection
    {
        return SiteFeature::active()->orderBy('sort_order')->get();
    }

    public function getAll(): Collection
    {
        return SiteFeature::orderBy('sort_order')->get();
    }

    public function findById(int $id): ?SiteFeature
    {
        return SiteFeature::find($id);
    }

    public function create(array $data): SiteFeature
    {
        return SiteFeature::create($data);
    }

    public function update(SiteFeature $feature, array $data): SiteFeature
    {
        $feature->update($data);
        return $feature->fresh();
    }

    public function delete(SiteFeature $feature): void
    {
        $feature->delete();
    }
}
