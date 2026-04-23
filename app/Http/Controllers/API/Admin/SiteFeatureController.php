<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\SiteFeature\SiteFeatureDestroyAction;
use App\Actions\SiteFeature\SiteFeatureIndexAction;
use App\Actions\SiteFeature\SiteFeatureStoreAction;
use App\Actions\SiteFeature\SiteFeatureUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteFeature\SiteFeatureStoreRequest;
use App\Http\Requests\SiteFeature\SiteFeatureUpdateRequest;
use App\Http\Resources\SiteFeature\SiteFeatureResource;
use App\Models\SiteFeature;
use Illuminate\Http\JsonResponse;

class SiteFeatureController extends Controller
{
    public function __construct(
        private readonly SiteFeatureIndexAction $indexAction,
        private readonly SiteFeatureStoreAction $storeAction,
        private readonly SiteFeatureUpdateAction $updateAction,
        private readonly SiteFeatureDestroyAction $destroyAction,
    ) {}

    public function index(): JsonResponse
    {
        $features = $this->indexAction->execute(activeOnly: false);

        return response()->json([
            'success' => true,
            'data'    => SiteFeatureResource::collection($features),
        ]);
    }

    public function store(SiteFeatureStoreRequest $request): JsonResponse
    {
        $feature = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'ویژگی سایت با موفقیت ایجاد شد',
            'data'    => new SiteFeatureResource($feature),
        ], 201);
    }

    public function show(SiteFeature $siteFeature): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new SiteFeatureResource($siteFeature),
        ]);
    }

    public function update(SiteFeatureUpdateRequest $request, SiteFeature $siteFeature): JsonResponse
    {
        $siteFeature = $this->updateAction->execute($request, $siteFeature);

        return response()->json([
            'success' => true,
            'message' => 'ویژگی سایت به‌روزرسانی شد',
            'data'    => new SiteFeatureResource($siteFeature),
        ]);
    }

    public function destroy(SiteFeature $siteFeature): JsonResponse
    {
        $this->destroyAction->execute($siteFeature);

        return response()->json([
            'success' => true,
            'message' => 'ویژگی سایت حذف شد',
        ]);
    }
}
