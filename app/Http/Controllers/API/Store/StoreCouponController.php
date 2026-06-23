<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\CouponStoreRequest;
use App\Http\Requests\Coupon\CouponUpdateRequest;
use App\Http\Resources\Coupon\CouponResource;
use App\Models\Coupon;
use IamApi\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreCouponController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    /**
     * List coupons for this store's organization.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = auth()->user()->organization_id;

        $query = Coupon::query()->where('organization_id', $orgId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        $coupons = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => CouponResource::collection($coupons),
            'meta'    => [
                'current_page' => $coupons->currentPage(),
                'last_page'    => $coupons->lastPage(),
                'per_page'     => $coupons->perPage(),
                'total'        => $coupons->total(),
            ],
        ]);
    }

    /**
     * Show a specific coupon — only if it belongs to this store.
     */
    public function show(Coupon $coupon): JsonResponse
    {
        $this->authorizeOwnership($coupon);

        $coupon->load('usages.user', 'usages.order');

        return response()->json([
            'success' => true,
            'data'    => new CouponResource($coupon),
        ]);
    }

    /**
     * Create a new store-level coupon.
     * Requires coupons.manage permission.
     */
    public function store(CouponStoreRequest $request): JsonResponse
    {
        $this->authorizeManage();

        $data = $request->validated();
        $data['organization_id'] = auth()->user()->organization_id;

        $coupon = Coupon::create($data);

        return response()->json([
            'success' => true,
            'message' => 'کوپن فروشگاه با موفقیت ایجاد شد',
            'data'    => new CouponResource($coupon),
        ], 201);
    }

    /**
     * Update a store coupon — only if it belongs to this store.
     * Requires coupons.manage permission.
     */
    public function update(CouponUpdateRequest $request, Coupon $coupon): JsonResponse
    {
        $this->authorizeOwnership($coupon);
        $this->authorizeManage();

        $coupon->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'کوپن به‌روزرسانی شد',
            'data'    => new CouponResource($coupon),
        ]);
    }

    /**
     * Delete a store coupon — only if it belongs to this store.
     * Requires coupons.manage permission.
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->authorizeOwnership($coupon);
        $this->authorizeManage();

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'کوپن حذف شد',
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Abort with 403 if the coupon does not belong to this store.
     */
    private function authorizeOwnership(Coupon $coupon): void
    {
        $orgId = auth()->user()->organization_id;

        abort_if(
            $coupon->organization_id !== $orgId,
            403,
            'این کوپن متعلق به فروشگاه شما نیست.',
        );
    }

    /**
     * Abort with 403 if the user lacks the coupons.manage permission.
     */
    private function authorizeManage(): void
    {
        $user = auth()->user();
        $org  = $user->organization;

        abort_unless(
            $this->organizationService->hasPermission($org, $user, 'coupons.manage'),
            403,
            'شما مجوز لازم برای این عملیات را ندارید.',
        );
    }
}
