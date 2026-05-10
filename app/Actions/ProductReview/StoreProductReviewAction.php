<?php

namespace App\Actions\ProductReview;

use App\Models\Product;
use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Validation\ValidationException;

class StoreProductReviewAction
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
    ) {}

    public function execute(Product $product, int $userId, array $data): ProductReview
    {
        if ($this->reviewRepository->existsForUser($product->id, $userId)) {
            throw ValidationException::withMessages([
                'product' => ['شما قبلاً برای این محصول نظر ثبت کرده‌اید'],
            ]);
        }

        return $this->reviewRepository->create([
            'product_id' => $product->id,
            'user_id'    => $userId,
            'rating'     => $data['rating'],
            'title'      => $data['title'] ?? null,
            'body'       => $data['body'],
            'status'     => 'pending',
        ]);
    }
}
