<?php

namespace App\Http\Controllers\API;

use App\Actions\ProductReview\GetProductReviewsAction;
use App\Actions\ProductReview\StoreProductReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductReview\StoreProductReviewRequest;
use App\Http\Resources\ProductReview\ProductReviewResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductReviewController extends Controller
{
    public function __construct(
        private readonly GetProductReviewsAction   $getReviewsAction,
        private readonly StoreProductReviewAction  $storeReviewAction,
    ) {}

    public function index(Product $product): JsonResponse
    {
        $reviews = $this->getReviewsAction->execute($product);

        return response()->json([
            'success' => true,
            'data'    => ProductReviewResource::collection($reviews),
        ]);
    }

    public function store(StoreProductReviewRequest $request, Product $product): JsonResponse
    {
        $review = $this->storeReviewAction->execute(
            $product,
            $request->user()->id,
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'نظر شما ثبت شد و پس از تأیید نمایش داده خواهد شد',
            'data'    => new ProductReviewResource($review),
        ], 201);
    }
}
