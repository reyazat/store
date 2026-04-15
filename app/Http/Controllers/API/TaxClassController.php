<?php

namespace App\Http\Controllers\API;

use App\Actions\TaxClass\DestroyTaxClassAction;
use App\Actions\TaxClass\SearchTaxClassAction;
use App\Actions\TaxClass\ShowTaxClassAction;
use App\Actions\TaxClass\StoreTaxClassAction;
use App\Actions\TaxClass\UpdateTaxClassAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaxClass\TaxClassSearchRequest;
use App\Http\Requests\TaxClass\TaxClassStoreRequest;
use App\Http\Requests\TaxClass\TaxClassUpdateRequest;
use App\Http\Resources\TaxClass\TaxClassDetailResource;
use App\Http\Resources\TaxClass\TaxClassListResource;
use App\Models\TaxClass;
use Illuminate\Http\JsonResponse;
use Throwable;

class TaxClassController extends Controller
{
    public function __construct(
        private readonly SearchTaxClassAction $searchAction,
        private readonly ShowTaxClassAction $showAction,
        private readonly StoreTaxClassAction $storeAction,
        private readonly UpdateTaxClassAction $updateAction,
        private readonly DestroyTaxClassAction $destroyAction
    ) {
    }

    public function index(TaxClassSearchRequest $request): JsonResponse
    {
        $taxClasses = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => TaxClassListResource::collection($taxClasses),
            'meta' => [
                'current_page' => $taxClasses->currentPage(),
                'last_page' => $taxClasses->lastPage(),
                'per_page' => $taxClasses->perPage(),
                'total' => $taxClasses->total(),
            ],
        ]);
    }

    public function show(TaxClass $taxClass): JsonResponse
    {
        $taxClass = $this->showAction->execute($taxClass);

        return response()->json([
            'success' => true,
            'data' => new TaxClassDetailResource($taxClass),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(TaxClassStoreRequest $request): JsonResponse
    {
        $taxClass = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new TaxClassDetailResource($taxClass),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(TaxClassUpdateRequest $request, TaxClass $taxClass): JsonResponse
    {
        $taxClass = $this->updateAction->execute($request, $taxClass);

        return response()->json([
            'success' => true,
            'data' => new TaxClassDetailResource($taxClass),
        ]);
    }

    public function destroy(TaxClass $taxClass): JsonResponse
    {
        $this->destroyAction->execute($taxClass);

        return response()->json([
            'success' => true,
            'message' => 'Tax class deleted successfully.',
        ]);
    }
}
