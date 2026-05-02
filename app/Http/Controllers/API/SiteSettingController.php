<?php

namespace App\Http\Controllers\API;

use App\Actions\SiteSetting\SiteSettingIndexAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SiteSettingController extends Controller
{
    public function __construct(
        private readonly SiteSettingIndexAction $indexAction
    ) {}

    public function index(): JsonResponse
    {
        $settings = $this->indexAction->execute();

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }
}
