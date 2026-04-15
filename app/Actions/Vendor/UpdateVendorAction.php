<?php

namespace App\Actions\Vendor;

use App\Http\Requests\Vendor\VendorUpdateRequest;
use App\Models\Vendor;
use App\Services\VendorService;
use Throwable;

class UpdateVendorAction
{
    public function __construct(private readonly VendorService $vendorService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(VendorUpdateRequest $request, Vendor $vendor): Vendor
    {
        return $this->vendorService->updateVendor($vendor, $request->validatedData())
            ->load(['policy', 'users:id'])
            ->loadCount(['users', 'listings']);
    }
}
