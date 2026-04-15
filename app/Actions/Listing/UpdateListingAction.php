<?php

namespace App\Actions\Listing;

use App\Http\Requests\Listing\ListingUpdateRequest;
use App\Models\Listing;
use App\Services\ListingService;
use Throwable;

class UpdateListingAction
{
    public function __construct(private readonly ListingService $listingService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(ListingUpdateRequest $request, Listing $listing): Listing
    {
        return $this->listingService->updateListing($listing, $request->validatedData())
            ->load(['product', 'vendor', 'listingVariants.variant', 'customerGroupPrices']);
    }
}
