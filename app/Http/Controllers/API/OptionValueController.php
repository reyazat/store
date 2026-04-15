<?php

namespace App\Http\Controllers\API;

use App\Actions\OptionValue\DestroyOptionValueAction;
use App\Actions\OptionValue\SearchOptionValueAction;
use App\Actions\OptionValue\ShowOptionValueAction;
use App\Actions\OptionValue\StoreOptionValueAction;
use App\Actions\OptionValue\UpdateOptionValueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OptionValue\OptionValueSearchRequest;
use App\Http\Requests\OptionValue\OptionValueStoreRequest;
use App\Http\Requests\OptionValue\OptionValueUpdateRequest;
use App\Http\Resources\OptionValue\OptionValueDetailResource;
use App\Http\Resources\OptionValue\OptionValueListResource;
use App\Models\OptionValue;
use Illuminate\Http\JsonResponse;
use Throwable;

class OptionValueController extends Controller
{
    public function __construct(
        private readonly SearchOptionValueAction $searchAction,
        private readonly ShowOptionValueAction $showAction,
        private readonly StoreOptionValueAction $storeAction,
        private readonly UpdateOptionValueAction $updateAction,
        private readonly DestroyOptionValueAction $destroyAction
    ) {
    }

    public function index(OptionValueSearchRequest $request): JsonResponse
    {
        $values = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => OptionValueListResource::collection($values),
            'meta' => [
                'current_page' => $values->currentPage(),
                'last_page' => $values->lastPage(),
                'per_page' => $values->perPage(),
                'total' => $values->total(),
            ],
        ]);
    }

    public function show(OptionValue $optionValue): JsonResponse
    {
        $optionValue = $this->showAction->execute($optionValue);

        return response()->json([
            'success' => true,
            'data' => new OptionValueDetailResource($optionValue),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(OptionValueStoreRequest $request): JsonResponse
    {
        $optionValue = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new OptionValueDetailResource($optionValue),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(OptionValueUpdateRequest $request, OptionValue $optionValue): JsonResponse
    {
        $optionValue = $this->updateAction->execute($request, $optionValue);

        return response()->json([
            'success' => true,
            'data' => new OptionValueDetailResource($optionValue),
        ]);
    }

    public function destroy(OptionValue $optionValue): JsonResponse
    {
        $this->destroyAction->execute($optionValue);

        return response()->json([
            'success' => true,
            'message' => 'Option value deleted successfully.',
        ]);
    }
}
