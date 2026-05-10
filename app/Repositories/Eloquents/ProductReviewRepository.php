<?php

namespace App\Repositories\Eloquents;

use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ProductReviewRepository implements ProductReviewRepositoryInterface
{
    public function getApprovedForProduct(int $productId): Collection
    {
        return ProductReview::query()
            ->where('product_id', $productId)
            ->approved()
            ->with('user:id,name')
            ->orderByDesc('approved_at')
            ->get();
    }

    public function getAllPaginated(int $perPage = 20): LengthAwarePaginator
    {
        return ProductReview::query()
            ->with(['product:id,name,slug', 'user:id,name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?ProductReview
    {
        return ProductReview::with(['product:id,name,slug', 'user:id,name'])->find($id);
    }

    public function create(array $data): ProductReview
    {
        return ProductReview::create($data);
    }

    public function approve(ProductReview $review): ProductReview
    {
        $review->update([
            'status'      => 'approved',
            'approved_at' => Carbon::now(),
        ]);

        return $review->fresh();
    }

    public function reject(ProductReview $review): ProductReview
    {
        $review->update([
            'status'      => 'rejected',
            'approved_at' => null,
        ]);

        return $review->fresh();
    }

    public function delete(ProductReview $review): void
    {
        $review->delete();
    }

    public function existsForUser(int $productId, int $userId): bool
    {
        return ProductReview::withTrashed()
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->exists();
    }
}
