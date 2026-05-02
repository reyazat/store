<?php

namespace App\Http\Controllers\API;

use App\Actions\NavLink\NavLinkIndexAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\NavLink\NavLinkResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavLinkController extends Controller
{
    public function __construct(
        private readonly NavLinkIndexAction $indexAction
    ) {}

    public function byGroup(Request $request, string $group): JsonResponse
    {
        $allowedGroups = ['header', 'footer_quick', 'footer_service'];

        if (! in_array($group, $allowedGroups, true)) {
            return response()->json(['success' => false, 'message' => 'گروه نامعتبر است.'], 422);
        }

        $links = $this->indexAction->execute(group: $group, activeOnly: true);

        return response()->json([
            'success' => true,
            'data'    => NavLinkResource::collection($links),
        ]);
    }
}
