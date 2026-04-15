<?php

namespace App\Actions\Listing;

use App\Models\Listing;

class ShowListingAction
{
    public function execute(Listing $listing): Listing
    {
        return $listing->load(['product', 'vendor', 'listingVariants.variant', 'customerGroupPrices.customerGroup']);
    }
}
