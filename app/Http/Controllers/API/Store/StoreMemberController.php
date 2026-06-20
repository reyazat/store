<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class StoreMemberController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function index(): JsonResponse
    {
        /** @var User $owner */
        $owner = auth()->user();
        $org   = $owner->organization;

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        $members = $org->users()->with('roles')->get()->map(function (User $member) use ($org) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
            return [
                'id'    => $member->id,
                'name'  => $member->name,
                'email' => $member->email,
                'role'  => $member->roles->first()?->name ?? 'member',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $members,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role'  => ['required', Rule::in(['admin', 'editor', 'member'])],
        ]);

        /** @var User $owner */
        $owner = auth()->user();
        $org   = $owner->organization;

        // Permission check: only owner/admin can manage members
        // Editor can manage content/products but not members
        if (!$this->organizationService->hasPermission($org, $owner, 'users.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه مدیریت اعضا را ندارید.',
            ], 403);
        }

        $target = User::where('email', $data['email'])->firstOrFail();

        if ($target->organization_id && $target->organization_id !== $org->id) {
            return response()->json([
                'success' => false,
                'message' => 'این کاربر در سازمان دیگری عضو است.',
            ], 422);
        }

        // Use OrganizationService (with Layer 1 & 2 Bridge) instead of direct repository
        $this->organizationService->addMember($org, $target, $data['role']);

        return response()->json([
            'success' => true,
            'message' => 'عضو با موفقیت اضافه شد.',
        ], 201);
    }

    public function update(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'editor', 'member'])],
        ]);

        /** @var User $owner */
        $owner = auth()->user();
        $org   = $owner->organization;

        // Permission check: only owner/admin can manage members
        if (!$this->organizationService->hasPermission($org, $owner, 'users.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه مدیریت اعضا را ندارید.',
            ], 403);
        }

        $target = User::findOrFail($userId);

        if ($target->organization_id !== $org->id) {
            return response()->json([
                'success' => false,
                'message' => 'این کاربر عضو فروشگاه شما نیست.',
            ], 403);
        }

        // Use OrganizationService (with Layer 1 & 2 Bridge) instead of direct repository
        $this->organizationService->updateMemberRole($org, $target, $data['role']);

        return response()->json([
            'success' => true,
            'message' => 'نقش عضو با موفقیت بروزرسانی شد.',
        ]);
    }

    public function destroy(int $userId): JsonResponse
    {
        /** @var User $owner */
        $owner = auth()->user();
        $org   = $owner->organization;

        // Permission check: only owner/admin can manage members
        if (!$this->organizationService->hasPermission($org, $owner, 'users.manage')) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه مدیریت اعضا را ندارید.',
            ], 403);
        }

        $target = User::findOrFail($userId);

        if ($target->id === $owner->id) {
            return response()->json([
                'success' => false,
                'message' => 'مالک فروشگاه نمی‌تواند خود را حذف کند.',
            ], 422);
        }

        if ($target->organization_id !== $org->id) {
            return response()->json([
                'success' => false,
                'message' => 'این کاربر عضو فروشگاه شما نیست.',
            ], 403);
        }

        // Use OrganizationService (with Layer 1 & 2 Bridge) instead of direct repository
        $this->organizationService->removeMember($org, $target);

        return response()->json([
            'success' => true,
            'message' => 'عضو با موفقیت حذف شد.',
        ]);
    }
}
