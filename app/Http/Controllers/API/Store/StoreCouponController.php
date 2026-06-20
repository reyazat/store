<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\CouponStoreRequest;
use App\Http\Requests\Coupon\CouponUpdateRequest;
use App\Http\Resources\Coupon\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreCouponController extends Controller
{
    /**
     * List coupons for this store owner's organization.
     * Store owners can only see their own store coupons.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Get coupons for this organization only
        $query = Coupon::query()->where('organization_id', $organizationId);

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
     * Show a specific coupon - only if it belongs to this store.
     */
    public function show(Coupon $coupon): JsonResponse
    {
        $this->authorize('view', $coupon);

        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Check if this coupon belongs to this store
        if ($coupon->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon does not belong to your store.',
            ], 403);
        }

        $coupon->load('usages.user', 'usages.order');

        return response()->json([
            'success' => true,
            'data'    => new CouponResource($coupon),
        ]);
    }

    /**
     * Create a new store-level coupon.
     */
    public function store(CouponStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Coupon::class);

        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Create coupon with organization_id
        $data = $request->validated();
        $data['organization_id'] = $organizationId;

        $coupon = Coupon::create($data);

        return response()->json([
            'success' => true,
            'message' => 'کوپن فروشگاه با موفقیت ایجاد شد',
            'data'    => new CouponResource($coupon),
        ], 201);
    }

    /**
     * Update a store coupon - only if it belongs to this store.
     */
    public function update(CouponUpdateRequest $request, Coupon $coupon): JsonResponse
    {
        $this->authorize('update', $coupon);

        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Check ownership
        if ($coupon->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon does not belong to your store.',
            ], 403);
        }

        $coupon->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'کوپن به‌روزرسانی شد',
            'data'    => new CouponResource($coupon),
        ]);
    }

    /**
     * Delete a store coupon - only if it belongs to this store.
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->authorize('delete', $coupon);

        $user = auth()->user();
        $organizationId = $user->organization_id;

        // Check ownership
        if ($coupon->organization_id !== $organizationId) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon does not belong to your store.',
            ], 403);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'کوپن حذف شد',
        ]);
    }
}
