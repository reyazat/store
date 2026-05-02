<?php

namespace App\Repositories\Contracts;

use App\Models\NavLink;
use Illuminate\Database\Eloquent\Collection;

interface NavLinkRepositoryInterface
{
    public function getActiveByGroup(string $group): Collection;

    public function getAll(): Collection;

    public function getByGroup(string $group): Collection;

    public function findById(int $id): ?NavLink;

    public function create(array $data): NavLink;

    public function update(NavLink $navLink, array $data): NavLink;

    public function delete(NavLink $navLink): void;
}
