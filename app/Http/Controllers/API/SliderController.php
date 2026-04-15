<?php

namespace App\Http\Controllers\API;

use App\Actions\Slider\SliderIndexAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Slider\SliderResource;
use Illuminate\Http\JsonResponse;

class SliderController extends Controller
{
    public function __construct(
        private readonly SliderIndexAction $indexAction
    ) {}

    public function index(): JsonResponse
    {
        $sliders = $this->indexAction->execute(activeOnly: true);

        return response()->json([
            'success' => true,
            'data'    => SliderResource::collection($sliders),
        ]);
    }
}
