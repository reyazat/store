<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\PromoBanner\PromoBannerDestroyAction;
use App\Actions\PromoBanner\PromoBannerIndexAction;
use App\Actions\PromoBanner\PromoBannerStoreAction;
use App\Actions\PromoBanner\PromoBannerUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PromoBanner\PromoBannerStoreRequest;
use App\Http\Requests\PromoBanner\PromoBannerUpdateRequest;
use App\Http\Resources\PromoBanner\PromoBannerResource;
use App\Models\PromoBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoBannerController extends Controller
{
    public function __construct(
        private readonly PromoBannerIndexAction $indexAction,
        private readonly PromoBannerStoreAction $storeAction,
        private readonly PromoBannerUpdateAction $updateAction,
        private readonly PromoBannerDestroyAction $destroyAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $placement = (string) $request->query('placement', 'home');
        $banners = $this->indexAction->execute(activeOnly: false, placement: $placement);

        return response()->json([
            'success' => true,
            'data'    => PromoBannerResource::collection($banners),
        ]);
    }

    public function store(PromoBannerStoreRequest $request): JsonResponse
    {
        $banner = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'بنر با موفقیت ایجاد شد',
            'data'    => new PromoBannerResource($banner),
        ], 201);
    }

    public function show(PromoBanner $promoBanner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new PromoBannerResource($promoBanner),
        ]);
    }

    public function update(PromoBannerUpdateRequest $request, PromoBanner $promoBanner): JsonResponse
    {
        $promoBanner = $this->updateAction->execute($request, $promoBanner);

        return response()->json([
            'success' => true,
            'message' => 'بنر به‌روزرسانی شد',
            'data'    => new PromoBannerResource($promoBanner),
        ]);
    }

    public function destroy(PromoBanner $promoBanner): JsonResponse
    {
        $this->destroyAction->execute($promoBanner);

        return response()->json([
            'success' => true,
            'message' => 'بنر حذف شد',
        ]);
    }
}
