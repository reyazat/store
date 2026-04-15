<?php

namespace App\Actions\Vendor;

use App\Models\Vendor;
use App\Services\VendorService;

class DestroyVendorAction
{
    public function __construct(private readonly VendorService $vendorService)
    {
    }

    public function execute(Vendor $vendor): void
    {
        $this->vendorService->deleteVendor($vendor);
    }
}
