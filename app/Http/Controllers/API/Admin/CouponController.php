<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
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
        
        $coupons = $query->latest()->paginate($request->per_page ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => $coupons->items(),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'status' => 'nullable|boolean',
        ]);
        
        $coupon = Coupon::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'کوپن با موفقیت ایجاد شد',
            'data' => $coupon,
        ], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->load('usages.user', 'usages.order');
        
        return response()->json([
            'success' => true,
            'data' => $coupon,
        ]);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|unique:coupons,code,' . $coupon->id . '|max:255',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:fixed,percentage',
            'value' => 'sometimes|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'status' => 'nullable|boolean',
        ]);
        
        $coupon->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'کوپن به‌روزرسانی شد',
            'data' => $coupon,
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
