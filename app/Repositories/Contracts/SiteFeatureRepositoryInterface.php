<?php

namespace App\Repositories\Contracts;

use App\Models\SiteFeature;
use Illuminate\Database\Eloquent\Collection;

interface SiteFeatureRepositoryInterface
{
    public function getActive(): Collection;

    public function getAll(): Collection;

    public function findById(int $id): ?SiteFeature;

    public function create(array $data): SiteFeature;

    public function update(SiteFeature $feature, array $data): SiteFeature;

    public function delete(SiteFeature $feature): void;
}
