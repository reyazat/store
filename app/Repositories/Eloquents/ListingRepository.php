<?php

namespace App\Repositories\Eloquents;

use App\Models\Listing;
use App\Repositories\Contracts\ListingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListingRepository implements ListingRepositoryInterface
{
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Listing::query()->with(['product', 'vendor', 'listingVariants']);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Listing
    {
        return Listing::with(['product', 'vendor', 'listingVariants.variant', 'customerGroupPrices'])->find($id);
    }

    public function findByProductAndVendor(int $productId, int $vendorId): ?Listing
    {
        return Listing::where('product_id', $productId)
            ->where('vendor_id', $vendorId)
            ->first();
    }
}
