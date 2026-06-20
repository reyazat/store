<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\Organization\StoreResource;
use IamApi\Actions\Organizations\UpdateOrganizationAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreProfileController extends Controller
{
    public function __construct(
        private readonly UpdateOrganizationAction $updateAction,
    ) {}

    public function show(): JsonResponse
    {
        /** @var User $owner */
        $owner = auth()->user();
        $org   = Organization::with('users')->findOrFail($owner->organization_id);

        return response()->json([
            'success' => true,
            'data'    => new StoreResource($org),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $owner */
        $owner = auth()->user();
        $org   = Organization::findOrFail($owner->organization_id);

        $result = $this->updateAction->execute(array_merge($data, [
            'organization' => $org,
            'user'         => $owner,
        ]));

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code'] ?? 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'پروفایل فروشگاه با موفقیت بروزرسانی شد.',
            'data'    => new StoreResource($org->fresh(['users'])),
        ]);
    }
}
