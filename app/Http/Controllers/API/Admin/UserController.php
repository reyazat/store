<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\User\ListUsersAction;
use App\Actions\User\LockUserAction;
use App\Actions\User\UnlockUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdminLockUserRequest;
use App\Http\Resources\User\AdminUserResource;
use IamApi\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly ListUsersAction $listUsersAction,
        private readonly LockUserAction $lockUserAction,
        private readonly UnlockUserAction $unlockUserAction,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $users = $this->listUsersAction->execute(
            $request->only(['search', 'status', 'per_page'])
        );

        return response()->json([
            'success' => true,
            'data'    => AdminUserResource::collection($users),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with('organization')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new AdminUserResource($user),
        ]);
    }

    public function lock(AdminLockUserRequest $request, int $id): JsonResponse
    {
        $targetUser = User::findOrFail($id);
        /** @var User $adminUser */
        $adminUser = auth()->user();

        if ($targetUser->isSuperAdmin() && !$adminUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'امکان قفل کردن حساب سوپر ادمین وجود ندارد.',
            ], 403);
        }

        $this->lockUserAction->execute(
            $targetUser,
            $adminUser,
            $request->validated('reason'),
            $request->validated('duration_minutes', 30)
        );

        return response()->json([
            'success' => true,
            'message' => 'حساب کاربری با موفقیت قفل شد.',
            'data'    => new AdminUserResource($targetUser->fresh()),
        ]);
    }

    public function unlock(int $id): JsonResponse
    {
        $targetUser = User::findOrFail($id);
        /** @var User $adminUser */
        $adminUser = auth()->user();

        $this->unlockUserAction->execute($targetUser, $adminUser);

        return response()->json([
            'success' => true,
            'message' => 'قفل حساب کاربری با موفقیت برداشته شد.',
            'data'    => new AdminUserResource($targetUser->fresh()),
        ]);
    }
}
