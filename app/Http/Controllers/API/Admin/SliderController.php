<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Slider\SliderDestroyAction;
use App\Actions\Slider\SliderIndexAction;
use App\Actions\Slider\SliderReorderAction;
use App\Actions\Slider\SliderStoreAction;
use App\Actions\Slider\SliderUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Slider\SliderStoreRequest;
use App\Http\Requests\Slider\SliderUpdateRequest;
use App\Http\Resources\Slider\SliderResource;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public function __construct(
        private readonly SliderIndexAction   $indexAction,
        private readonly SliderStoreAction   $storeAction,
        private readonly SliderUpdateAction  $updateAction,
        private readonly SliderDestroyAction $destroyAction,
        private readonly SliderReorderAction $reorderAction,
    ) {}

    public function index(): JsonResponse
    {
        $sliders = $this->indexAction->execute(activeOnly: false);

        return response()->json([
            'success' => true,
            'data'    => SliderResource::collection($sliders),
        ]);
    }

    public function store(SliderStoreRequest $request): JsonResponse
    {
        $slider = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'اسلایدر با موفقیت ایجاد شد',
            'data'    => new SliderResource($slider),
        ], 201);
    }

    public function show(Slider $slider): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new SliderResource($slider),
        ]);
    }

    public function update(SliderUpdateRequest $request, Slider $slider): JsonResponse
    {
        $slider = $this->updateAction->execute($request, $slider);

        return response()->json([
            'success' => true,
            'message' => 'اسلایدر به‌روزرسانی شد',
            'data'    => new SliderResource($slider),
        ]);
    }

    public function destroy(Slider $slider): JsonResponse
    {
        $this->destroyAction->execute($slider);

        return response()->json([
            'success' => true,
            'message' => 'اسلایدر حذف شد',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:sliders,id',
        ]);

        $this->reorderAction->execute($request->ids);

        return response()->json([
            'success' => true,
            'message' => 'ترتیب اسلایدرها ذخیره شد',
        ]);
    }
}
