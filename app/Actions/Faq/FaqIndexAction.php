<?php

namespace App\Actions\Faq;

use App\Repositories\Contracts\FaqRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FaqIndexAction
{
    public function __construct(
        private readonly FaqRepositoryInterface $repository
    ) {}

    public function execute(bool $activeOnly = true): Collection
    {
        return $activeOnly
            ? $this->repository->getAllActive()
            : $this->repository->getAll();
    }
}
