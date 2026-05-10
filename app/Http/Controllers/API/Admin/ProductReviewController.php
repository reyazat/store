<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\ProductReview\ApproveProductReviewAction;
use App\Actions\ProductReview\DestroyProductReviewAction;
use App\Actions\ProductReview\RejectProductReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductReview\ProductReviewResource;
use App\Models\ProductReview;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function __construct(
        private readonly ProductReviewRepositoryInterface $reviewRepository,
        private readonly ApproveProductReviewAction       $approveAction,
        private readonly RejectProductReviewAction        $rejectAction,
        private readonly DestroyProductReviewAction       $destroyAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $reviews = $this->reviewRepository->getAllPaginated($perPage);

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

    public function approve(ProductReview $review): JsonResponse
    {
        $updated = $this->approveAction->execute($review);

        return response()->json([
            'success' => true,
            'message' => 'نظر تأیید شد',
            'data'    => new ProductReviewResource($updated),
        ]);
    }

    public function reject(ProductReview $review): JsonResponse
    {
        $updated = $this->rejectAction->execute($review);

        return response()->json([
            'success' => true,
            'message' => 'نظر رد شد',
            'data'    => new ProductReviewResource($updated),
        ]);
    }

    public function destroy(ProductReview $review): JsonResponse
    {
        $this->destroyAction->execute($review);

        return response()->json([
            'success' => true,
            'message' => 'نظر حذف شد',
        ]);
    }
}
