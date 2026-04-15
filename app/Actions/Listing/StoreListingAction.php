<?php

namespace App\Actions\Listing;

use App\Http\Requests\Listing\ListingStoreRequest;
use App\Models\Listing;
use App\Services\ListingService;
use Throwable;

class StoreListingAction
{
    public function __construct(private readonly ListingService $listingService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(ListingStoreRequest $request): Listing
    {
        return $this->listingService->createListing($request->validatedData())
            ->load(['product', 'vendor', 'listingVariants.variant', 'customerGroupPrices']);
    }
}
