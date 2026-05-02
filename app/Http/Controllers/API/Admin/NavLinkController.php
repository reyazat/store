<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\NavLink\NavLinkDestroyAction;
use App\Actions\NavLink\NavLinkIndexAction;
use App\Actions\NavLink\NavLinkStoreAction;
use App\Actions\NavLink\NavLinkUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\NavLink\NavLinkStoreRequest;
use App\Http\Requests\NavLink\NavLinkUpdateRequest;
use App\Http\Resources\NavLink\NavLinkResource;
use App\Models\NavLink;
use Illuminate\Http\JsonResponse;

class NavLinkController extends Controller
{
    public function __construct(
        private readonly NavLinkIndexAction   $indexAction,
        private readonly NavLinkStoreAction   $storeAction,
        private readonly NavLinkUpdateAction  $updateAction,
        private readonly NavLinkDestroyAction $destroyAction,
    ) {}

    public function index(): JsonResponse
    {
        $links = $this->indexAction->execute(group: null, activeOnly: false);

        return response()->json([
            'success' => true,
            'data'    => NavLinkResource::collection($links),
        ]);
    }

    public function store(NavLinkStoreRequest $request): JsonResponse
    {
        $navLink = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'لینک با موفقیت ایجاد شد',
            'data'    => new NavLinkResource($navLink),
        ], 201);
    }

    public function show(NavLink $navLink): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new NavLinkResource($navLink),
        ]);
    }

    public function update(NavLinkUpdateRequest $request, NavLink $navLink): JsonResponse
    {
        $navLink = $this->updateAction->execute($request, $navLink);

        return response()->json([
            'success' => true,
            'message' => 'لینک به‌روزرسانی شد',
            'data'    => new NavLinkResource($navLink),
        ]);
    }

    public function destroy(NavLink $navLink): JsonResponse
    {
        $this->destroyAction->execute($navLink);

        return response()->json([
            'success' => true,
            'message' => 'لینک حذف شد',
        ]);
    }
}
