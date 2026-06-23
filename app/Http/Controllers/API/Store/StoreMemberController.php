<?php

namespace App\Http\Controllers\API\Store;

use App\Actions\Store\LockStoreMemberAction;
use App\Actions\Store\UnlockStoreMemberAction;
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
        private readonly OrganizationService             $organizationService,
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly LockStoreMemberAction           $lockAction,
        private readonly UnlockStoreMemberAction         $unlockAction,
    ) {}

    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * Return enriched member list for the store.
     * All authenticated org-members can view the list (users.view permission).
     */
    public function index(): JsonResponse
    {
        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        $members = $org->users()->with('roles')->get()->map(function (User $member) use ($org) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

            $isLocked = $member->locked_until && $member->locked_until->isFuture();

            return [
                'id'           => $member->id,
                'name'         => $member->name,
                'email'        => $member->email,
                'phone'        => $member->phone,
                'role'         => $member->roles->first()?->name ?? 'member',
                'is_locked'    => $isLocked,
                'locked_until' => $isLocked ? $member->locked_until->toISOString() : null,
                'last_login_at'=> $member->last_login_at?->toISOString(),
                'joined_at'    => $member->created_at->toISOString(),
            ];
        });

        return response()->json(['success' => true, 'data' => $members]);
    }

    // ─── Add existing user ────────────────────────────────────────────────────

    /**
     * Directly add an existing system user to the store by their email.
     * Requires users.manage permission.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role'  => ['required', Rule::in(['admin', 'editor', 'user', 'member'])],
        ]);

        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        if (!$this->organizationService->hasPermission($org, $actor, 'users.manage')) {
            return response()->json(['success' => false, 'message' => 'شما اجازه مدیریت اعضا را ندارید.'], 403);
        }

        $target = User::where('email', $data['email'])->firstOrFail();

        // Block adding super admins to a store
        if ($target->is_super_admin) {
            return response()->json(['success' => false, 'message' => 'امکان افزودن سوپر ادمین به فروشگاه وجود ندارد.'], 422);
        }

        if ($target->organization_id && $target->organization_id !== $org->id) {
            return response()->json(['success' => false, 'message' => 'این کاربر در سازمان دیگری عضو است.'], 422);
        }

        $this->organizationService->addMember($org, $target, $data['role']);

        return response()->json(['success' => true, 'message' => 'عضو با موفقیت اضافه شد.'], 201);
    }

    // ─── Change role ──────────────────────────────────────────────────────────

    public function update(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'editor', 'user', 'member'])],
        ]);

        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        if (!$this->organizationService->hasPermission($org, $actor, 'users.manage')) {
            return response()->json(['success' => false, 'message' => 'شما اجازه مدیریت اعضا را ندارید.'], 403);
        }

        $target = User::findOrFail($userId);

        if ($target->organization_id !== $org->id) {
            return response()->json(['success' => false, 'message' => 'این کاربر عضو فروشگاه شما نیست.'], 403);
        }

        // Cannot demote self if owner
        if ($actor->id === $target->id) {
            $actorRole = $this->organizationService->getUserRole($org, $actor);
            if ($actorRole === 'owner') {
                return response()->json(['success' => false, 'message' => 'مالک فروشگاه نمی‌تواند نقش خود را تغییر دهد.'], 422);
            }
        }

        // Cannot change the owner's role unless actor is also owner
        $targetRole = $this->organizationService->getUserRole($org, $target);
        $actorRole  = $this->organizationService->getUserRole($org, $actor);
        if ($targetRole === 'owner' && $actorRole !== 'owner') {
            return response()->json(['success' => false, 'message' => 'مدیر نمی‌تواند نقش مالک فروشگاه را تغییر دهد.'], 403);
        }

        $this->organizationService->updateMemberRole($org, $target, $data['role']);

        return response()->json(['success' => true, 'message' => 'نقش عضو با موفقیت بروزرسانی شد.']);
    }

    // ─── Remove ───────────────────────────────────────────────────────────────

    public function destroy(int $userId): JsonResponse
    {
        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        if (!$this->organizationService->hasPermission($org, $actor, 'users.manage')) {
            return response()->json(['success' => false, 'message' => 'شما اجازه مدیریت اعضا را ندارید.'], 403);
        }

        $target = User::findOrFail($userId);

        if ($target->id === $actor->id) {
            return response()->json(['success' => false, 'message' => 'نمی‌توانید خودتان را از فروشگاه حذف کنید.'], 422);
        }

        if ($target->organization_id !== $org->id) {
            return response()->json(['success' => false, 'message' => 'این کاربر عضو فروشگاه شما نیست.'], 403);
        }

        $this->organizationService->removeMember($org, $target);

        return response()->json(['success' => true, 'message' => 'عضو با موفقیت حذف شد.']);
    }

    // ─── Lock / Unlock ────────────────────────────────────────────────────────

    public function lock(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            'reason'           => ['required', 'string', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
        ]);

        /** @var User $actor */
        $actor  = auth()->user();
        $org    = $actor->organization;
        $target = User::findOrFail($userId);

        $result = $this->lockAction->execute($org, $actor, $target, $data['reason'], $data['duration_minutes']);

        $status = $result['status'] ?? 200;
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function unlock(int $userId): JsonResponse
    {
        /** @var User $actor */
        $actor  = auth()->user();
        $org    = $actor->organization;
        $target = User::findOrFail($userId);

        $result = $this->unlockAction->execute($org, $actor, $target);

        $status = $result['status'] ?? 200;
        unset($result['status']);

        return response()->json($result, $status);
    }
}
