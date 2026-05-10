<?php

namespace App\Actions\ProductReview;

use App\Models\Product;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GetProductReviewsAction
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
    ) {}

    public function execute(Product $product): Collection
    {
        return $this->reviewRepository->getApprovedForProduct($product->id);
    }
}
