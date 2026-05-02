<?php

namespace App\Actions\NavLink;

use App\Models\NavLink;
use App\Repositories\Contracts\NavLinkRepositoryInterface;

class NavLinkDestroyAction
{
    public function __construct(
        private readonly NavLinkRepositoryInterface $repository
    ) {}

    public function execute(NavLink $navLink): void
    {
        $this->repository->delete($navLink);
    }
}
