<?php

namespace App\Actions\Listing;

use App\Models\Listing;
use App\Services\ListingService;

class DestroyListingAction
{
    public function __construct(private readonly ListingService $listingService)
    {
    }

    public function execute(Listing $listing): void
    {
        $this->listingService->deleteListing($listing);
    }
}
