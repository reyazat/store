<?php

namespace App\Actions\CustomerGroup;

use App\Http\Requests\CustomerGroup\CustomerGroupStoreRequest;
use App\Models\CustomerGroup;
use App\Services\CustomerGroupService;
use Throwable;

class StoreCustomerGroupAction
{
    public function __construct(private readonly CustomerGroupService $customerGroupService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(CustomerGroupStoreRequest $request): CustomerGroup
    {
        return $this->customerGroupService->createCustomerGroup($request->validatedData())->load(['users:id']);
    }
}
