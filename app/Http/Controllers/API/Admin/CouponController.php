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
        $query = Coupon::query();

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
        $coupon = Coupon::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'کوپن با موفقیت ایجاد شد',
            'data'    => new CouponResource($coupon),
        ], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->load('usages.user', 'usages.order');

        return response()->json([
            'success' => true,
            'data'    => new CouponResource($coupon),
        ]);
    }

    public function update(CouponUpdateRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'کوپن به‌روزرسانی شد',
            'data'    => new CouponResource($coupon),
        ]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'کوپن حذف شد',
        ]);
    }
}
