<?php

namespace App\Actions\Vendor;

use App\Http\Requests\Vendor\VendorStoreRequest;
use App\Models\Vendor;
use App\Services\VendorService;
use Throwable;

class StoreVendorAction
{
    public function __construct(private readonly VendorService $vendorService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(VendorStoreRequest $request): Vendor
    {
        return $this->vendorService->createVendor($request->validatedData())
            ->load(['policy', 'users:id'])
            ->loadCount(['users', 'listings']);
    }
}
