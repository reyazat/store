<?php

namespace App\Http\Controllers\API;

use App\Actions\Vendor\DestroyVendorAction;
use App\Actions\Vendor\SearchVendorAction;
use App\Actions\Vendor\ShowVendorAction;
use App\Actions\Vendor\StoreVendorAction;
use App\Actions\Vendor\UpdateVendorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorSearchRequest;
use App\Http\Requests\Vendor\VendorStoreRequest;
use App\Http\Requests\Vendor\VendorUpdateRequest;
use App\Http\Resources\Vendor\VendorDetailResource;
use App\Http\Resources\Vendor\VendorListResource;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Throwable;

class VendorController extends Controller
{
    public function __construct(
        private readonly SearchVendorAction $searchAction,
        private readonly ShowVendorAction $showAction,
        private readonly StoreVendorAction $storeAction,
        private readonly UpdateVendorAction $updateAction,
        private readonly DestroyVendorAction $destroyAction
    ) {
    }

    public function index(VendorSearchRequest $request): JsonResponse
    {
        $vendors = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => VendorListResource::collection($vendors),
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'per_page' => $vendors->perPage(),
                'total' => $vendors->total(),
            ],
        ]);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        $vendor = $this->showAction->execute($vendor);

        return response()->json([
            'success' => true,
            'data' => new VendorDetailResource($vendor),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(VendorStoreRequest $request): JsonResponse
    {
        $vendor = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new VendorDetailResource($vendor),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(VendorUpdateRequest $request, Vendor $vendor): JsonResponse
    {
        $vendor = $this->updateAction->execute($request, $vendor);

        return response()->json([
            'success' => true,
            'data' => new VendorDetailResource($vendor),
        ]);
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->destroyAction->execute($vendor);

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully.',
        ]);
    }
}
