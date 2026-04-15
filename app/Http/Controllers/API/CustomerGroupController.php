<?php

namespace App\Http\Controllers\API;

use App\Actions\CustomerGroup\DestroyCustomerGroupAction;
use App\Actions\CustomerGroup\SearchCustomerGroupAction;
use App\Actions\CustomerGroup\ShowCustomerGroupAction;
use App\Actions\CustomerGroup\StoreCustomerGroupAction;
use App\Actions\CustomerGroup\UpdateCustomerGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerGroup\CustomerGroupSearchRequest;
use App\Http\Requests\CustomerGroup\CustomerGroupStoreRequest;
use App\Http\Requests\CustomerGroup\CustomerGroupUpdateRequest;
use App\Http\Resources\CustomerGroup\CustomerGroupDetailResource;
use App\Http\Resources\CustomerGroup\CustomerGroupListResource;
use App\Models\CustomerGroup;
use Illuminate\Http\JsonResponse;
use Throwable;

class CustomerGroupController extends Controller
{
    public function __construct(
        private readonly SearchCustomerGroupAction $searchAction,
        private readonly ShowCustomerGroupAction $showAction,
        private readonly StoreCustomerGroupAction $storeAction,
        private readonly UpdateCustomerGroupAction $updateAction,
        private readonly DestroyCustomerGroupAction $destroyAction
    ) {
    }

    public function index(CustomerGroupSearchRequest $request): JsonResponse
    {
        $groups = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => CustomerGroupListResource::collection($groups),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    public function show(CustomerGroup $customerGroup): JsonResponse
    {
        $customerGroup = $this->showAction->execute($customerGroup);

        return response()->json([
            'success' => true,
            'data' => new CustomerGroupDetailResource($customerGroup),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(CustomerGroupStoreRequest $request): JsonResponse
    {
        $customerGroup = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new CustomerGroupDetailResource($customerGroup),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(CustomerGroupUpdateRequest $request, CustomerGroup $customerGroup): JsonResponse
    {
        $customerGroup = $this->updateAction->execute($request, $customerGroup);

        return response()->json([
            'success' => true,
            'data' => new CustomerGroupDetailResource($customerGroup),
        ]);
    }

    public function destroy(CustomerGroup $customerGroup): JsonResponse
    {
        $this->destroyAction->execute($customerGroup);

        return response()->json([
            'success' => true,
            'message' => 'Customer group deleted successfully.',
        ]);
    }
}
