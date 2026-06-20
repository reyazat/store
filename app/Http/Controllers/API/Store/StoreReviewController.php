<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreReviewController extends Controller
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * List reviews for products belonging to this store owner's organization.
     * Store owners can only see reviews for their own products.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Get reviews for products from this organization
        $reviews = ProductReview::query()
            ->whereHas('product', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->with(['product', 'user'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('rating'), fn($q, $rating) => $q->where('rating', $rating))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $reviews->items(),
            'meta'    => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    /**
     * Show a specific review - only if it belongs to this store's product.
     */
    public function show(ProductReview $review): JsonResponse
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Check if this review belongs to a product from this store
        if ($review->product->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'This review does not belong to your store.',
            ], 403);
        }

        $review->load(['product', 'user']);

        return response()->json([
            'success' => true,
            'data'    => $review,
        ]);
    }

    /**
     * Approve a review (store owner can moderate reviews for their products).
     */
    public function approve(ProductReview $review): JsonResponse
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Check ownership
        if ($review->product->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'This review does not belong to your store.',
            ], 403);
        }

        $review->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => 'Review approved successfully.',
            'data'    => $review,
        ]);
    }

    /**
     * Reject a review (store owner can moderate reviews for their products).
     */
    public function reject(ProductReview $review): JsonResponse
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Check ownership
        if ($review->product->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'This review does not belong to your store.',
            ], 403);
        }

        $review->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Review rejected successfully.',
            'data'    => $review,
        ]);
    }
}
