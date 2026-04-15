<?php

namespace App\Actions\CustomerGroup;

use App\Http\Requests\CustomerGroup\CustomerGroupUpdateRequest;
use App\Models\CustomerGroup;
use App\Services\CustomerGroupService;
use Throwable;

class UpdateCustomerGroupAction
{
    public function __construct(private readonly CustomerGroupService $customerGroupService)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(CustomerGroupUpdateRequest $request, CustomerGroup $customerGroup): CustomerGroup
    {
        return $this->customerGroupService->updateCustomerGroup($customerGroup, $request->validatedData())->load(['users:id']);
    }
}
