<?php

namespace App\Http\Controllers\API\Admin;

use App\Actions\Product\Admin\ShowAdminProductAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Option\OptionDetailResource;
use App\Http\Resources\Product\Admin\AdminProductDetailResource;
use App\Models\Option;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class AdminProductController extends Controller
{
    public function __construct(
        private readonly ShowAdminProductAction $showAdminAction,
    ) {}

    public function show(Product $product): JsonResponse
    {
        $product = $this->showAdminAction->execute($product);

        return response()->json([
            'success' => true,
            'data'    => new AdminProductDetailResource($product),
        ]);
    }

    public function availableOptions(): JsonResponse
    {
        $options = Option::query()
            ->with('values')
            ->orderBy('sorted')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => OptionDetailResource::collection($options),
        ]);
    }
}
