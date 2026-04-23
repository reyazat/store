<?php

namespace App\Http\Controllers\API;

use App\Actions\SiteFeature\SiteFeatureIndexAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\SiteFeature\SiteFeatureResource;
use Illuminate\Http\JsonResponse;

class SiteFeatureController extends Controller
{
    public function __construct(
        private readonly SiteFeatureIndexAction $indexAction
    ) {}

    public function index(): JsonResponse
    {
        $features = $this->indexAction->execute(activeOnly: true);

        return response()->json([
            'success' => true,
            'data'    => SiteFeatureResource::collection($features),
        ]);
    }
}
