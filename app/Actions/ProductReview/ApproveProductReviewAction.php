<?php

namespace App\Actions\ProductReview;

use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;

class ApproveProductReviewAction
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
    ) {}

    public function execute(ProductReview $review): ProductReview
    {
        return $this->reviewRepository->approve($review);
    }
}
