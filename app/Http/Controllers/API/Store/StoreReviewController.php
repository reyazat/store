<?php

namespace App\Http\Controllers\API\Store;

use App\Actions\ProductReview\ApproveProductReviewAction;
use App\Actions\ProductReview\RejectProductReviewAction;
use App\Actions\ProductReview\ReplyToReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductReview\ReplyToReviewRequest;
use App\Http\Resources\ProductReview\ProductReviewResource;
use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use IamApi\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreReviewController extends Controller
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
        private readonly ApproveProductReviewAction       $approveAction,
        private readonly RejectProductReviewAction        $rejectAction,
        private readonly ReplyToReviewAction              $replyAction,
        private readonly OrganizationService              $organizationService,
    ) {}

    /**
     * List reviews for products belonging to this store's organization.
     * Supports filtering by status and rating.
     */
    public function index(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $filters = $request->only(['status', 'rating']);
        $perPage = $request->integer('per_page', 15);

        $reviews = $this->reviewRepository->getForOrganizationPaginated(
            $user->organization_id,
            $filters,
            $perPage,
        );

        return response()->json([
            'success' => true,
            'data'    => ProductReviewResource::collection($reviews->items()),
            'meta'    => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    /**
     * Show a specific review — only if it belongs to this store's product.
     */
    public function show(ProductReview $review): JsonResponse
    {
        $this->authorizeOwnership($review);
        $review->load(['product', 'user']);

        return response()->json([
            'success' => true,
            'data'    => new ProductReviewResource($review),
        ]);
    }

    /**
     * Approve a review (sets status=approved and approved_at timestamp).
     * Requires products.manage permission.
     */
    public function approve(ProductReview $review): JsonResponse
    {
        $this->authorizeOwnership($review);
        $this->authorizeManage();

        $updated = $this->approveAction->execute($review);

        return response()->json([
            'success' => true,
            'message' => 'نظر تأیید شد',
            'data'    => new ProductReviewResource($updated),
        ]);
    }

    /**
     * Reject a review (sets status=rejected, clears approved_at).
     * Requires products.manage permission.
     */
    public function reject(ProductReview $review): JsonResponse
    {
        $this->authorizeOwnership($review);
        $this->authorizeManage();

        $updated = $this->rejectAction->execute($review);

        return response()->json([
            'success' => true,
            'message' => 'نظر رد شد',
            'data'    => new ProductReviewResource($updated),
        ]);
    }

    /**
     * Add or update the store owner's reply to a customer review.
     * Requires products.manage permission.
     */
    public function reply(ReplyToReviewRequest $request, ProductReview $review): JsonResponse
    {
        $this->authorizeOwnership($review);
        $this->authorizeManage();

        $updated = $this->replyAction->execute($review, $request->input('body'));

        return response()->json([
            'success' => true,
            'message' => 'پاسخ شما ثبت شد',
            'data'    => new ProductReviewResource($updated),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Abort with 403 if the review's product does not belong to this store.
     */
    private function authorizeOwnership(ProductReview $review): void
    {
        $orgId = auth()->user()->organization_id;

        abort_if(
            $review->product->organization_id !== $orgId,
            403,
            'این نظر متعلق به محصولات فروشگاه شما نیست.',
        );
    }

    /**
     * Abort with 403 if the authenticated user lacks the products.manage permission
     * (only owner/admin/editor roles have this permission by default).
     */
    private function authorizeManage(): void
    {
        $user = auth()->user();
        $org  = $user->organization;

        abort_unless(
            $this->organizationService->hasPermission($org, $user, 'reviews.manage'),
            403,
            'شما مجوز لازم برای این عملیات را ندارید.',
        );
    }
}
