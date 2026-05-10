<?php

namespace App\Actions\Faq;

use App\Http\Requests\Faq\FaqUpdateRequest;
use App\Models\Faq;
use App\Repositories\Contracts\FaqRepositoryInterface;

class FaqUpdateAction
{
    public function __construct(
        private readonly FaqRepositoryInterface $repository
    ) {}

    public function execute(FaqUpdateRequest $request, Faq $faq): Faq
    {
        return $this->repository->update($faq, $request->validated());
    }
}
