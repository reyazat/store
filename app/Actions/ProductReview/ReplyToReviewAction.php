<?php

namespace App\Actions\ProductReview;

use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;

class ReplyToReviewAction
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
    ) {}

    public function execute(ProductReview $review, string $body): ProductReview
    {
        return $this->reviewRepository->reply($review, $body);
    }
}
