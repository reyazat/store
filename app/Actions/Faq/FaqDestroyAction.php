<?php

namespace App\Actions\Faq;

use App\Models\Faq;
use App\Repositories\Contracts\FaqRepositoryInterface;

class FaqDestroyAction
{
    public function __construct(
        private readonly FaqRepositoryInterface $repository
    ) {}

    public function execute(Faq $faq): void
    {
        $this->repository->delete($faq);
    }
}
