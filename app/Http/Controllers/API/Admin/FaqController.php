<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Faq\FaqDestroyAction;
use App\Actions\Faq\FaqIndexAction;
use App\Actions\Faq\FaqStoreAction;
use App\Actions\Faq\FaqUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faq\FaqStoreRequest;
use App\Http\Requests\Faq\FaqUpdateRequest;
use App\Http\Resources\Faq\FaqResource;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function __construct(
        private readonly FaqIndexAction   $indexAction,
        private readonly FaqStoreAction   $storeAction,
        private readonly FaqUpdateAction  $updateAction,
        private readonly FaqDestroyAction $destroyAction,
    ) {}

    public function index(): JsonResponse
    {
        $faqs = $this->indexAction->execute(activeOnly: false);

        return response()->json([
            'success' => true,
            'data'    => FaqResource::collection($faqs),
        ]);
    }

    public function store(FaqStoreRequest $request): JsonResponse
    {
        $faq = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت ایجاد شد',
            'data'    => new FaqResource($faq),
        ], 201);
    }

    public function show(Faq $faq): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new FaqResource($faq),
        ]);
    }

    public function update(FaqUpdateRequest $request, Faq $faq): JsonResponse
    {
        $faq = $this->updateAction->execute($request, $faq);

        return response()->json([
            'success' => true,
            'message' => 'سوال به‌روزرسانی شد',
            'data'    => new FaqResource($faq),
        ]);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $this->destroyAction->execute($faq);

        return response()->json([
            'success' => true,
            'message' => 'سوال حذف شد',
        ]);
    }
}
