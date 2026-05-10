<?php

namespace App\Actions\Faq;

use App\Http\Requests\Faq\FaqStoreRequest;
use App\Models\Faq;
use App\Repositories\Contracts\FaqRepositoryInterface;

class FaqStoreAction
{
    public function __construct(
        private readonly FaqRepositoryInterface $repository
    ) {}

    public function execute(FaqStoreRequest $request): Faq
    {
        return $this->repository->create($request->validated());
    }
}
