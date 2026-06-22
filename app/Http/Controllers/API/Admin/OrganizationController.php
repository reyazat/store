<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Organization\CreateStoreWithOwnerAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Organization\StoreResource;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\User\AdminUserResource;
use App\Models\Product;
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
        private readonly CreateStoreWithOwnerAction $createStoreAction,
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
            'name'             => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'status'           => ['nullable', 'in:active,inactive,suspended'],
            // Existing owner mode
            'owner_id'         => ['nullable', 'integer', 'exists:users,id', 'prohibits:new_owner'],
            // New owner mode
            'new_owner'        => ['nullable', 'array', 'prohibits:owner_id'],
            'new_owner.name'   => ['required_with:new_owner', 'string', 'max:255'],
            'new_owner.email'  => ['required_with:new_owner', 'string', 'email', 'max:255', 'unique:users,email'],
            'new_owner.password' => ['required_with:new_owner', 'string', 'min:8'],
            'new_owner.phone'  => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $result = $this->createStoreAction->execute($request->only([
                'name', 'description', 'status', 'owner_id', 'new_owner',
            ]));
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        /** @var Organization $org */
        $org = $result['organization']->load('users');

        return response()->json([
            'success'       => true,
            'message'       => 'فروشگاه با موفقیت ایجاد شد.',
            'owner_created' => $result['owner_created'],
            'data'          => new StoreResource($org),
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

    /**
     * Assign an existing user as owner of the store.
     * Supports two modes via request body:
     *   - owner_id: assign an existing user
     *   - new_owner: create a new user and assign them as owner
     */
    public function assignOwner(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            // Existing user mode
            'owner_id'           => ['nullable', 'integer', 'exists:users,id', 'prohibits:new_owner'],
            // New user mode
            'new_owner'          => ['nullable', 'array', 'prohibits:owner_id'],
            'new_owner.name'     => ['required_with:new_owner', 'string', 'max:255'],
            'new_owner.email'    => ['required_with:new_owner', 'string', 'email', 'max:255', 'unique:users,email'],
            'new_owner.password' => ['required_with:new_owner', 'string', 'min:8'],
            'new_owner.phone'    => ['nullable', 'string', 'max:20'],
        ]);

        if (empty($request->input('owner_id')) && empty($request->input('new_owner'))) {
            return response()->json(['success' => false, 'message' => 'Either owner_id or new_owner must be provided.'], 422);
        }

        try {
            // Reuse the action which handles both modes
            $result = $this->createStoreAction->assignOwnerToExisting(
                $organization,
                $request->integer('owner_id') ?: null,
                $request->input('new_owner')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        /** @var User $owner */
        $owner = $result['owner'];

        return response()->json([
            'success'       => true,
            'message'       => 'مالک فروشگاه با موفقیت تعیین شد.',
            'owner_created' => $result['owner_created'],
            'data'          => new StoreResource($organization->fresh(['users'])),
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
