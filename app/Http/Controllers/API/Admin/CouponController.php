<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\CouponStoreRequest;
use App\Http\Requests\Coupon\CouponUpdateRequest;
use App\Http\Resources\Coupon\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Super admin manages only platform-level coupons (organization_id = null)
        $query = Coupon::query()->whereNull('organization_id');

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

    public function store(CouponStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Coupon::class);

        // Create platform-level coupon (organization_id = null)
        $data = $request->validated();
        $data['organization_id'] = null;

        $coupon = Coupon::create($data);

        return response()->json([
            'success' => true,
            'message' => 'کوپن پلتفرم با موفقیت ایجاد شد',
            'data'    => new CouponResource($coupon),
        ], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $this->authorize('view', $coupon);

        // Ensure this is a platform-level coupon
        if ($coupon->organization_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'This is a store-level coupon. Manage it via store panel.',
            ], 403);
        }

        $coupon->load('usages.user', 'usages.order');

        return response()->json([
            'success' => true,
            'data'    => new CouponResource($coupon),
        ]);
    }

    public function update(CouponUpdateRequest $request, Coupon $coupon): JsonResponse
    {
        $this->authorize('update', $coupon);

        // Ensure this is a platform-level coupon
        if ($coupon->organization_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'This is a store-level coupon. Manage it via store panel.',
            ], 403);
        }

        $coupon->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'کوپن به‌روزرسانی شد',
            'data'    => new CouponResource($coupon),
        ]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->authorize('delete', $coupon);

        // Ensure this is a platform-level coupon
        if ($coupon->organization_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'This is a store-level coupon. Manage it via store panel.',
            ], 403);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'کوپن حذف شد',
        ]);
    }
}
