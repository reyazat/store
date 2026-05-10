<?php

namespace App\Repositories\Contracts;

use App\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductReviewRepositoryInterface
{
    public function getApprovedForProduct(int $productId): Collection;

    public function getAllPaginated(int $perPage = 20): LengthAwarePaginator;

    public function findById(int $id): ?ProductReview;

    public function create(array $data): ProductReview;

    public function approve(ProductReview $review): ProductReview;

    public function reject(ProductReview $review): ProductReview;

    public function delete(ProductReview $review): void;

    public function existsForUser(int $productId, int $userId): bool;
}
