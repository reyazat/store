<?php

namespace App\Http\Controllers\API;

use App\Actions\Listing\DestroyListingAction;
use App\Actions\Listing\SearchListingAction;
use App\Actions\Listing\ShowListingAction;
use App\Actions\Listing\StoreListingAction;
use App\Actions\Listing\UpdateListingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\ListingSearchRequest;
use App\Http\Requests\Listing\ListingStoreRequest;
use App\Http\Requests\Listing\ListingUpdateRequest;
use App\Http\Resources\Listing\ListingDetailResource;
use App\Http\Resources\Listing\ListingListResource;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Throwable;

class ListingController extends Controller
{
    public function __construct(
        private readonly SearchListingAction $searchAction,
        private readonly ShowListingAction $showAction,
        private readonly StoreListingAction $storeAction,
        private readonly UpdateListingAction $updateAction,
        private readonly DestroyListingAction $destroyAction
    ) {
    }

    public function index(ListingSearchRequest $request): JsonResponse
    {
        $listings = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => ListingListResource::collection($listings),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
            ],
        ]);
    }

    public function show(Listing $listing): JsonResponse
    {
        $listing = $this->showAction->execute($listing);

        return response()->json([
            'success' => true,
            'data' => new ListingDetailResource($listing),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(ListingStoreRequest $request): JsonResponse
    {
        $listing = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new ListingDetailResource($listing),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(ListingUpdateRequest $request, Listing $listing): JsonResponse
    {
        $listing = $this->updateAction->execute($request, $listing);

        return response()->json([
            'success' => true,
            'data' => new ListingDetailResource($listing),
        ]);
    }

    public function destroy(Listing $listing): JsonResponse
    {
        $this->destroyAction->execute($listing);

        return response()->json([
            'success' => true,
            'message' => 'Listing deleted successfully.',
        ]);
    }
}
