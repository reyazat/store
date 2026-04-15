<?php

namespace App\Http\Controllers\API;

use App\Actions\Option\DestroyOptionAction;
use App\Actions\Option\SearchOptionAction;
use App\Actions\Option\ShowOptionAction;
use App\Actions\Option\StoreOptionAction;
use App\Actions\Option\UpdateOptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Option\OptionSearchRequest;
use App\Http\Requests\Option\OptionStoreRequest;
use App\Http\Requests\Option\OptionUpdateRequest;
use App\Http\Resources\Option\OptionDetailResource;
use App\Http\Resources\Option\OptionListResource;
use App\Models\Option;
use Illuminate\Http\JsonResponse;
use Throwable;

class OptionController extends Controller
{
    public function __construct(
        private readonly SearchOptionAction $searchAction,
        private readonly ShowOptionAction $showAction,
        private readonly StoreOptionAction $storeAction,
        private readonly UpdateOptionAction $updateAction,
        private readonly DestroyOptionAction $destroyAction
    ) {
    }

    public function index(OptionSearchRequest $request): JsonResponse
    {
        $options = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => OptionListResource::collection($options),
            'meta' => [
                'current_page' => $options->currentPage(),
                'last_page' => $options->lastPage(),
                'per_page' => $options->perPage(),
                'total' => $options->total(),
            ],
        ]);
    }

    public function show(Option $option): JsonResponse
    {
        $option = $this->showAction->execute($option);

        return response()->json([
            'success' => true,
            'data' => new OptionDetailResource($option),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(OptionStoreRequest $request): JsonResponse
    {
        $option = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new OptionDetailResource($option),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(OptionUpdateRequest $request, Option $option): JsonResponse
    {
        $option = $this->updateAction->execute($request, $option);

        return response()->json([
            'success' => true,
            'data' => new OptionDetailResource($option),
        ]);
    }

    public function destroy(Option $option): JsonResponse
    {
        $this->destroyAction->execute($option);

        return response()->json([
            'success' => true,
            'message' => 'Option deleted successfully.',
        ]);
    }
}
