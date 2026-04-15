<?php

namespace App\Services;

use App\Models\Listing;
use App\Repositories\Contracts\ListingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class ListingService
{
    public function __construct(private readonly ListingRepositoryInterface $listingRepository)
    {
    }

    public function getListings(array $filters = []): LengthAwarePaginator
    {
        return $this->listingRepository->getAll($filters);
    }

    public function getListingById(int $id): ?Listing
    {
        return $this->listingRepository->findById($id);
    }

    /**
     * @throws Throwable
     */
    public function createListing(array $data): Listing
    {
        return DB::transaction(function () use ($data) {
            $listing = Listing::create($this->listingAttributes($data));
            $this->syncRelations($listing, $data);

            return $listing->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function updateListing(Listing $listing, array $data): Listing
    {
        return DB::transaction(function () use ($listing, $data) {
            $listing->update($this->listingAttributes($data));
            $this->syncRelations($listing, $data);

            return $listing->refresh();
        });
    }

    public function deleteListing(Listing $listing): void
    {
        $listing->delete();
    }

    private function listingAttributes(array $data): array
    {
        return Arr::only($data, [
            'product_id',
            'vendor_id',
            'status',
            'base_price',
            'min_order_qty',
        ]);
    }

    private function syncRelations(Listing $listing, array $data): void
    {
        if (array_key_exists('variants', $data)) {
            $listing->listingVariants()->delete();
            foreach (($data['variants'] ?? []) as $variantData) {
                $listing->listingVariants()->create([
                    'product_variant_id' => $variantData['product_variant_id'],
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'] ?? 0,
                    'status' => $variantData['status'] ?? 'active',
                ]);
            }
        }

        if (array_key_exists('customer_group_prices', $data)) {
            $listing->customerGroupPrices()->delete();
            foreach (($data['customer_group_prices'] ?? []) as $priceData) {
                $listing->customerGroupPrices()->create([
                    'customer_group_id' => $priceData['customer_group_id'],
                    'price' => $priceData['price'],
                ]);
            }
        }
    }
}
