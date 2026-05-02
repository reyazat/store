<?php

namespace App\Actions\NavLink;

use App\Http\Requests\NavLink\NavLinkUpdateRequest;
use App\Models\NavLink;
use App\Repositories\Contracts\NavLinkRepositoryInterface;

class NavLinkUpdateAction
{
    public function __construct(
        private readonly NavLinkRepositoryInterface $repository
    ) {}

    public function execute(NavLinkUpdateRequest $request, NavLink $navLink): NavLink
    {
        return $this->repository->update($navLink, $request->validated());
    }
}
