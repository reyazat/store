<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use IamApi\Actions\Organizations\CreateInvitationAction;
use IamApi\Actions\Organizations\ResendInvitationAction;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationInvitationRepositoryInterface;
use IamApi\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manage email invitations for the store's organisation.
 *
 * All write operations require the users.invite permission (owner + admin).
 */
class StoreInvitationController extends Controller
{
    public function __construct(
        private readonly CreateInvitationAction                    $createAction,
        private readonly ResendInvitationAction                    $resendAction,
        private readonly OrganizationInvitationRepositoryInterface $invitationRepository,
        private readonly OrganizationService                       $organizationService,
    ) {}

    // ─── List pending invitations ─────────────────────────────────────────────

    public function index(): JsonResponse
    {
        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        $invitations = OrganizationInvitation::where('organization_id', $org->id)
            ->whereIn('status', ['pending'])
            ->with('invitedBy:id,name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(OrganizationInvitation $inv) => [
                'uuid'       => $inv->uuid,
                'email'      => $inv->email,
                'role'       => $inv->role,
                'status'     => $inv->status,
                'is_expired' => $inv->expires_at && now()->gt($inv->expires_at),
                'expires_at' => $inv->expires_at?->toISOString(),
                'invited_by' => $inv->invitedBy?->name,
                'created_at' => $inv->created_at->toISOString(),
            ]);

        return response()->json(['success' => true, 'data' => $invitations]);
    }

    // ─── Send invitation ──────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', Rule::in(['admin', 'editor', 'user', 'member'])],
        ]);

        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        // Check for an already-pending invitation for the same email in this org
        $existing = OrganizationInvitation::where('organization_id', $org->id)
            ->where('email', $data['email'])
            ->where('status', 'pending')
            ->whereNull('expires_at')->orWhere('expires_at', '>', now())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'یک دعوتنامه در انتظار برای این ایمیل وجود دارد.',
            ], 422);
        }

        $result = $this->createAction->execute([
            'organization' => $org,
            'user'         => $actor,
            'email'        => $data['email'],
            'role'         => $data['role'],
        ]);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code'] ?? 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'دعوتنامه با موفقیت ارسال شد.',
            'data'    => $result['data'],
        ], 201);
    }

    // ─── Cancel invitation ────────────────────────────────────────────────────

    public function destroy(string $uuid): JsonResponse
    {
        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        if (!$this->organizationService->hasPermission($org, $actor, 'users.invite')) {
            return response()->json(['success' => false, 'message' => 'شما اجازه لغو دعوتنامه را ندارید.'], 403);
        }

        $invitation = OrganizationInvitation::where('uuid', $uuid)->firstOrFail();

        if ($invitation->organization_id !== $org->id) {
            return response()->json(['success' => false, 'message' => 'دعوتنامه یافت نشد.'], 404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'این دعوتنامه قابل لغو نیست.'], 422);
        }

        $this->invitationRepository->cancelInvitation($invitation);

        return response()->json(['success' => true, 'message' => 'دعوتنامه با موفقیت لغو شد.']);
    }

    // ─── Resend invitation ────────────────────────────────────────────────────

    public function resend(string $uuid): JsonResponse
    {
        /** @var User $actor */
        $actor = auth()->user();
        $org   = $actor->organization;

        $invitation = OrganizationInvitation::where('uuid', $uuid)->firstOrFail();

        if ($invitation->organization_id !== $org->id) {
            return response()->json(['success' => false, 'message' => 'دعوتنامه یافت نشد.'], 404);
        }

        $result = $this->resendAction->execute([
            'organization'  => $org,
            'user'          => $actor,
            'invitation_id' => $invitation->id,
        ]);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code'] ?? 422);
        }

        return response()->json(['success' => true, 'message' => 'دعوتنامه مجدداً ارسال شد.']);
    }
}
