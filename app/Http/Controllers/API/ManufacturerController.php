<?php

namespace App\Http\Controllers\API;

use App\Actions\Manufacturer\DestroyManufacturerAction;
use App\Actions\Manufacturer\SearchManufacturerAction;
use App\Actions\Manufacturer\ShowManufacturerAction;
use App\Actions\Manufacturer\StoreManufacturerAction;
use App\Actions\Manufacturer\UpdateManufacturerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manufacturer\ManufacturerSearchRequest;
use App\Http\Requests\Manufacturer\ManufacturerStoreRequest;
use App\Http\Requests\Manufacturer\ManufacturerUpdateRequest;
use App\Http\Resources\Manufacturer\ManufacturerDetailResource;
use App\Http\Resources\Manufacturer\ManufacturerListResource;
use App\Models\Manufacturer;
use Illuminate\Http\JsonResponse;
use Throwable;

class ManufacturerController extends Controller
{
    public function __construct(
        private readonly SearchManufacturerAction $searchAction,
        private readonly ShowManufacturerAction $showAction,
        private readonly StoreManufacturerAction $storeAction,
        private readonly UpdateManufacturerAction $updateAction,
        private readonly DestroyManufacturerAction $destroyAction
    ) {
    }

    public function index(ManufacturerSearchRequest $request): JsonResponse
    {
        $manufacturers = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => ManufacturerListResource::collection($manufacturers),
            'meta' => [
                'current_page' => $manufacturers->currentPage(),
                'last_page' => $manufacturers->lastPage(),
                'per_page' => $manufacturers->perPage(),
                'total' => $manufacturers->total(),
            ],
        ]);
    }

    public function show(Manufacturer $manufacturer): JsonResponse
    {
        $manufacturer = $this->showAction->execute($manufacturer);

        return response()->json([
            'success' => true,
            'data' => new ManufacturerDetailResource($manufacturer),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(ManufacturerStoreRequest $request): JsonResponse
    {
        $manufacturer = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new ManufacturerDetailResource($manufacturer),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(ManufacturerUpdateRequest $request, Manufacturer $manufacturer): JsonResponse
    {
        $manufacturer = $this->updateAction->execute($request, $manufacturer);

        return response()->json([
            'success' => true,
            'data' => new ManufacturerDetailResource($manufacturer),
        ]);
    }

    public function destroy(Manufacturer $manufacturer): JsonResponse
    {
        $this->destroyAction->execute($manufacturer);

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer deleted successfully.',
        ]);
    }
}
