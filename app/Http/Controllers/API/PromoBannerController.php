<?php

namespace App\Http\Controllers\API;

use App\Actions\PromoBanner\PromoBannerIndexAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PromoBanner\PromoBannerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoBannerController extends Controller
{
    public function __construct(
        private readonly PromoBannerIndexAction $indexAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        $placement = (string) $request->query('placement', 'home');
        $banners = $this->indexAction->execute(activeOnly: true, placement: $placement);

        return response()->json([
            'success' => true,
            'data'    => PromoBannerResource::collection($banners),
        ]);
    }
}
