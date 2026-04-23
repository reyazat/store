<?php

namespace App\Http\Controllers\API;

use App\Actions\Category\DestroyCategoryAction;
use App\Actions\Category\SearchCategoryAction;
use App\Actions\Category\ShowCategoryAction;
use App\Actions\Category\StoreCategoryAction;
use App\Actions\Category\UpdateCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategorySearchRequest;
use App\Http\Requests\Category\CategoryStoreRequest;
use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Http\Resources\Category\CategoryDetailResource;
use App\Http\Resources\Category\CategoryListResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(
        private readonly SearchCategoryAction $searchAction,
        private readonly ShowCategoryAction $showAction,
        private readonly StoreCategoryAction $storeAction,
        private readonly UpdateCategoryAction $updateAction,
        private readonly DestroyCategoryAction $destroyAction,
        private readonly CategoryService $categoryService
    ) {
    }

    public function index(CategorySearchRequest $request): JsonResponse
    {
        $categories = $this->searchAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => CategoryListResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        $category = $this->showAction->execute($category);

        return response()->json([
            'success' => true,
            'data' => new CategoryDetailResource($category),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $category = $this->storeAction->execute($request);

        return response()->json([
            'success' => true,
            'data' => new CategoryDetailResource($category),
        ], 201);
    }

    /**
     * @throws Throwable
     */
    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        $category = $this->updateAction->execute($request, $category);

        return response()->json([
            'success' => true,
            'data' => new CategoryDetailResource($category),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->destroyAction->execute($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function headerCategories(): JsonResponse
    {
        $categories = $this->categoryService->getHeaderCategories(6);

        return response()->json([
            'success' => true,
            'data' => CategoryListResource::collection($categories),
        ]);
    }
}
