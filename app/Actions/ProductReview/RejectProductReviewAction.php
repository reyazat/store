<?php

namespace App\Actions\ProductReview;

use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;

class RejectProductReviewAction
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
    ) {}

    public function execute(ProductReview $review): ProductReview
    {
        return $this->reviewRepository->reject($review);
    }
}
