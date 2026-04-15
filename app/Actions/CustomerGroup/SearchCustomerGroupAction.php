<?php

namespace App\Actions\CustomerGroup;

use App\Http\Requests\CustomerGroup\CustomerGroupSearchRequest;
use App\Services\CustomerGroupService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchCustomerGroupAction
{
    public function __construct(private readonly CustomerGroupService $customerGroupService)
    {
    }

    public function execute(CustomerGroupSearchRequest $request): LengthAwarePaginator
    {
        return $this->customerGroupService->getCustomerGroups($request->validatedFilters());
    }
}
