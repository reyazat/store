<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Coupon\CouponResource;
use App\Http\Resources\Order\AdminOrderDetailResource;
use App\Http\Resources\Order\AdminOrderListResource;
use App\Http\Resources\Product\ProductListResource;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use IamApi\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provides read-only access to store-scoped data for Super Admin.
 *
 * Super Admin cannot use /v1/store/* (blocked by StoreOwnerMiddleware).
 * Instead, this controller exposes organization-scoped views under
 * /v1/admin/organizations/{organization}/*
 */
class AdminStoreDataController extends Controller
{
    /**
     * List members of a specific organization.
     */
    public function members(Organization $organization): JsonResponse
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $members = $organization->users()->with('roles')->get()->map(function ($member) use ($organization) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
            return [
                'id'    => $member->id,
                'name'  => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'role'  => $member->roles->first()?->name ?? 'member',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $members,
        ]);
    }

    /**
     * List orders that contain products from a specific organization.
     */
    public function orders(Request $request, Organization $organization): JsonResponse
    {
        $orders = Order::query()
            ->whereHas('items', function ($query) use ($organization) {
                $query->whereHas('product', function ($q) use ($organization) {
                    $q->where('organization_id', $organization->id);
                });
            })
            ->with(['user', 'items.product', 'orderAddress', 'latestTransaction'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('payment_status'), fn($q, $status) => $q->where('payment_status', $status))
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => AdminOrderListResource::collection($orders),
            'meta'    => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * Show a specific order — only if it has products from this organization.
     */
    public function orderDetail(Organization $organization, Order $order): JsonResponse
    {
        $hasStoreProducts = $order->items()
            ->whereHas('product', fn($q) => $q->where('organization_id', $organization->id))
            ->exists();

        if (!$hasStoreProducts) {
            return response()->json([
                'success' => false,
                'message' => 'This order does not contain products from this store.',
            ], 404);
        }

        $order->load(['user', 'items.product', 'orderAddress', 'latestTransaction', 'histories.user']);

        return response()->json([
            'success' => true,
            'data'    => new AdminOrderDetailResource($order),
        ]);
    }

    /**
     * List reviews for products belonging to a specific organization.
     */
    public function reviews(Request $request, Organization $organization): JsonResponse
    {
        $reviews = ProductReview::query()
            ->whereHas('product', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->with(['product', 'user'])
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->input('rating'), fn($q, $rating) => $q->where('rating', $rating))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $reviews->items(),
            'meta'    => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    /**
     * List coupons belonging to a specific organization.
     */
    public function coupons(Request $request, Organization $organization): JsonResponse
    {
        $query = Coupon::query()->where('organization_id', $organization->id);

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
     * Show a specific coupon — only if it belongs to this organization.
     */
    public function couponDetail(Organization $organization, Coupon $coupon): JsonResponse
    {
        if ($coupon->organization_id !== $organization->id) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon does not belong to this store.',
            ], 404);
        }

        $coupon->load('usages.user', 'usages.order');

        return response()->json([
            'success' => true,
            'data'    => new CouponResource($coupon),
        ]);
    }

    /**
     * List products belonging to a specific organization (read-only for Super Admin).
     * Mirrors what the store owner sees at GET /store/products, but scoped by org UUID.
     */
    public function products(Request $request, Organization $organization): JsonResponse
    {
        $query = Product::query()
            ->where('organization_id', $organization->id)
            ->with([
                'manufacturer',
                'listings' => fn($q) => $q->where('status', 'active')
                    ->with(['listingVariants' => fn($lv) => $lv->where('status', 1)]),
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('base_sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('status', (int) $request->input('status'));
        }

        $products = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => ProductListResource::collection($products),
            'meta'    => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }
}
