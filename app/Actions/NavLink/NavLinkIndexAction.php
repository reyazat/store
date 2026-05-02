<?php

namespace App\Actions\NavLink;

use App\Repositories\Contracts\NavLinkRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NavLinkIndexAction
{
    public function __construct(
        private readonly NavLinkRepositoryInterface $repository
    ) {}

    public function execute(?string $group = null, bool $activeOnly = false): Collection
    {
        if ($group !== null) {
            return $activeOnly
                ? $this->repository->getActiveByGroup($group)
                : $this->repository->getByGroup($group);
        }

        return $this->repository->getAll();
    }
}
