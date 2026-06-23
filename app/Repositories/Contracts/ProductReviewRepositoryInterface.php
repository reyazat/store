<?php

namespace App\Repositories\Contracts;

use App\Models\ProductReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductReviewRepositoryInterface
{
    public function getApprovedForProduct(int $productId): Collection;

    public function getAllPaginated(int $perPage = 20): LengthAwarePaginator;

    /** List reviews scoped to an organization, with optional status/rating filters. */
    public function getForOrganizationPaginated(int $organizationId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?ProductReview;

    public function create(array $data): ProductReview;

    public function approve(ProductReview $review): ProductReview;

    public function reject(ProductReview $review): ProductReview;

    /** Store owner/admin reply to a customer review. */
    public function reply(ProductReview $review, string $body): ProductReview;

    public function delete(ProductReview $review): void;

    public function existsForUser(int $productId, int $userId): bool;
}
