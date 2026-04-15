<?php

namespace App\Actions\CustomerGroup;

use App\Models\CustomerGroup;
use App\Services\CustomerGroupService;

class DestroyCustomerGroupAction
{
    public function __construct(private readonly CustomerGroupService $customerGroupService)
    {
    }

    public function execute(CustomerGroup $customerGroup): void
    {
        $this->customerGroupService->deleteCustomerGroup($customerGroup);
    }
}
