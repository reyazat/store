<?php

namespace App\Repositories\Eloquents;

use App\Models\NavLink;
use App\Repositories\Contracts\NavLinkRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NavLinkRepository implements NavLinkRepositoryInterface
{
    public function getActiveByGroup(string $group): Collection
    {
        return NavLink::active()->inGroup($group)->orderBy('sort_order')->get();
    }

    public function getAll(): Collection
    {
        return NavLink::orderBy('group')->orderBy('sort_order')->get();
    }

    public function getByGroup(string $group): Collection
    {
        return NavLink::inGroup($group)->orderBy('sort_order')->get();
    }

    public function findById(int $id): ?NavLink
    {
        return NavLink::find($id);
    }

    public function create(array $data): NavLink
    {
        return NavLink::create($data);
    }

    public function update(NavLink $navLink, array $data): NavLink
    {
        $navLink->update($data);
        return $navLink->fresh();
    }

    public function delete(NavLink $navLink): void
    {
        $navLink->delete();
    }
}
