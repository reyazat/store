<?php

namespace App\Actions\NavLink;

use App\Http\Requests\NavLink\NavLinkStoreRequest;
use App\Models\NavLink;
use App\Repositories\Contracts\NavLinkRepositoryInterface;

class NavLinkStoreAction
{
    public function __construct(
        private readonly NavLinkRepositoryInterface $repository
    ) {}

    public function execute(NavLinkStoreRequest $request): NavLink
    {
        return $this->repository->create($request->validated());
    }
}
