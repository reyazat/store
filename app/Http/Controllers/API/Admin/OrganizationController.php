<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Organization\StoreResource;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\User\AdminUserResource;
use App\Models\Product;
use IamApi\Actions\Organizations\CreateOrganizationAction;
use IamApi\Actions\Organizations\UpdateOrganizationAction;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly CreateOrganizationAction $createAction,
        private readonly UpdateOrganizationAction $updateAction,
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Organization::query()
            ->withCount('users')
            ->where('name', '!=', 'Default Organization');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $stores = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => StoreResource::collection($stores->load('users')),
            'meta'    => [
                'current_page' => $stores->currentPage(),
                'last_page'    => $stores->lastPage(),
                'per_page'     => $stores->perPage(),
                'total'        => $stores->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['nullable', 'in:active,inactive,suspended'],
            'owner_id'    => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $result = $this->createAction->execute($request->only(['name', 'description', 'status', 'owner_id']));

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code'] ?? 400);
        }

        $org = Organization::with('users')->where('uuid', $result['data']['organization']['uuid'])->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'فروشگاه با موفقیت ایجاد شد.',
            'data'    => new StoreResource($org),
        ], 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new StoreResource($organization->load('users')),
        ]);
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'name'        => ['sometimes', 'string', 'max:255', Rule::unique('organizations', 'name')->ignore($organization->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['nullable', 'in:active,inactive,suspended'],
        ]);

        $result = $this->updateAction->execute(array_merge(
            $request->only(['name', 'description', 'status']),
            ['organization' => $organization, 'user' => auth()->user()]
        ));

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code'] ?? 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'فروشگاه با موفقیت بروزرسانی شد.',
            'data'    => new StoreResource($organization->fresh(['users'])),
        ]);
    }

    public function assignOwner(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'owner_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        /** @var User $user */
        $user = User::findOrFail($request->integer('owner_id'));

        if ($user->is_super_admin) {
            return response()->json(['success' => false, 'message' => 'Super admin cannot be assigned as store owner.'], 422);
        }

        // If user belongs to a different organization, remove them first (transfer)
        if ($user->organization_id !== null && $user->organization_id !== $organization->id) {
            $previousOrg = Organization::find($user->organization_id);
            if ($previousOrg) {
                $this->organizationRepository->removeMember($previousOrg, $user);
            }
        }

        $this->organizationRepository->addMember($organization, $user, 'owner');

        return response()->json([
            'success' => true,
            'message' => 'مالک فروشگاه با موفقیت تعیین شد.',
            'data'    => ['owner' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]],
        ]);
    }

    /**
     * Super admin overview of a specific store.
     * Provides full read-only visibility: profile, team, products, and order summary.
     * This is the "impersonate view" — super admin sees exactly what the store owner sees.
     */
    public function overview(Request $request, Organization $organization): JsonResponse
    {
        $organization->load(['users']);

        $owner = $organization->users->first(fn($u) => $u->hasRole('owner'));

        // Team breakdown by role
        $team = $organization->users->groupBy(fn($u) => $u->pivot->role ?? 'member');

        // Products belonging to this organization (via organization_id on products table if exists,
        // or fallback to products owned by org members)
        $products = Product::query()
            ->where('organization_id', $organization->id)
            ->with(['listings', 'manufacturer'])
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => [
                'store'    => new StoreResource($organization),
                'owner'    => $owner ? [
                    'id'    => $owner->id,
                    'uuid'  => $owner->uuid,
                    'name'  => $owner->name,
                    'email' => $owner->email,
                    'phone' => $owner->phone,
                ] : null,
                'team' => [
                    'total'   => $organization->users->count(),
                    'owners'  => $team->get('owner', collect())->count(),
                    'admins'  => $team->get('admin', collect())->count(),
                    'editors' => $team->get('editor', collect())->count(),
                    'members' => $team->get('member', collect())->count(),
                    'list'    => $organization->users->map(fn($u) => [
                        'id'    => $u->id,
                        'name'  => $u->name,
                        'email' => $u->email,
                        'role'  => $u->pivot->role ?? 'member',
                    ]),
                ],
                'products' => [
                    'data' => ProductListResource::collection($products->items()),
                    'meta' => [
                        'current_page' => $products->currentPage(),
                        'last_page'    => $products->lastPage(),
                        'per_page'     => $products->perPage(),
                        'total'        => $products->total(),
                    ],
                ],
            ],
        ]);
    }
}
