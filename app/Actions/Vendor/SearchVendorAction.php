<?php

namespace App\Actions\Vendor;

use App\Http\Requests\Vendor\VendorSearchRequest;
use App\Services\VendorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchVendorAction
{
    public function __construct(private readonly VendorService $vendorService)
    {
    }

    public function execute(VendorSearchRequest $request): LengthAwarePaginator
    {
        return $this->vendorService->getVendors($request->validatedFilters());
    }
}
