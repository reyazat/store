<?php

namespace App\Http\Controllers\API\Store;

use App\Actions\Store\GetStoreDashboardAction;
use App\Http\Controllers\Controller;
use IamApi\Models\User;
use Illuminate\Http\JsonResponse;

class StoreDashboardController extends Controller
{
    public function __construct(
        private readonly GetStoreDashboardAction $action,
    ) {}

    public function index(): JsonResponse
    {
        /** @var User $user */
        $user  = auth()->user();
        $orgId = $user->organization_id;

        return response()->json([
            'success' => true,
            'data'    => $this->action->execute($orgId),
        ]);
    }
}
