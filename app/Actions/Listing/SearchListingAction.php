<?php

namespace App\Actions\Listing;

use App\Http\Requests\Listing\ListingSearchRequest;
use App\Services\ListingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchListingAction
{
    public function __construct(private readonly ListingService $listingService)
    {
    }

    public function execute(ListingSearchRequest $request): LengthAwarePaginator
    {
        return $this->listingService->getListings($request->validatedFilters());
    }
}
