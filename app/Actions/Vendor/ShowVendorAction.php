<?php

namespace App\Actions\Vendor;

use App\Models\Vendor;

class ShowVendorAction
{
    public function execute(Vendor $vendor): Vendor
    {
        return $vendor->load(['policy', 'users:id'])->loadCount(['users', 'listings']);
    }
}
