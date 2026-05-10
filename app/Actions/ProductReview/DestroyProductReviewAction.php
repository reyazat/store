<?php

namespace App\Actions\ProductReview;

use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;

class DestroyProductReviewAction
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
    ) {}

    public function execute(ProductReview $review): void
    {
        $this->reviewRepository->delete($review);
    }
}
