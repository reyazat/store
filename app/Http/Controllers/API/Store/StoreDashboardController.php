<?php

namespace App\Http\Controllers\API\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
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
        $orgId = $owner->organization_id;

        $org = Organization::withCount('users')->findOrFail($orgId);

        $members = $org->users;
        $byRole  = $members->groupBy(fn($u) => $u->pivot->role ?? 'member');

        // Products belonging to this store
        $totalProducts = Product::where('organization_id', $orgId)->count();

        // Orders that contain products from this store
        $ordersQuery = Order::whereHas('items', function ($q) use ($orgId) {
            $q->whereHas('product', fn($p) => $p->where('organization_id', $orgId));
        });

        $totalOrders   = $ordersQuery->count();
        $pendingOrders = (clone $ordersQuery)->where('status', 'pending')->count();

        // Reviews for products from this store
        $pendingReviews = ProductReview::whereHas('product', fn($q) => $q->where('organization_id', $orgId))
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'store' => [
                    'id'            => $org->id,
                    'uuid'          => $org->uuid,
                    'name'          => $org->name,
                    'description'   => $org->description,
                    'status'        => $org->status,
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
                'stats' => [
                    'total_products'  => $totalProducts,
                    'total_orders'    => $totalOrders,
                    'pending_orders'  => $pendingOrders,
                    'pending_reviews' => $pendingReviews,
                ],
            ],
        ]);
    }
}
