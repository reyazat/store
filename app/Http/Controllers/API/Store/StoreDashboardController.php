<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Http\JsonResponse;

class StoreDashboardController extends Controller
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function index(): JsonResponse
    {
        /** @var User $owner */
        $owner = auth()->user();
        $org   = Organization::withCount('users')->findOrFail($owner->organization_id);

        $members = $org->users;
        $byRole  = $members->groupBy(fn($u) => $u->pivot->role ?? 'member');

        return response()->json([
            'success' => true,
            'data'    => [
                'store' => [
                    'id'           => $org->id,
                    'uuid'         => $org->uuid,
                    'name'         => $org->name,
                    'description'  => $org->description,
                    'status'       => $org->status,
                    'members_count' => $org->users_count,
                ],
                'owner' => [
                    'id'    => $owner->id,
                    'name'  => $owner->name,
                    'email' => $owner->email,
                ],
                'team' => [
                    'owners'  => $byRole->get('owner', collect())->count(),
                    'admins'  => $byRole->get('admin', collect())->count(),
                    'editors' => $byRole->get('editor', collect())->count(),
                    'members' => $byRole->get('member', collect())->count(),
                ],
            ],
        ]);
    }
}
