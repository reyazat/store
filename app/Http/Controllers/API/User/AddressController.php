<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddressStoreRequest;
use App\Http\Requests\Address\AddressUpdateRequest;
use App\Http\Resources\Address\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->get();

        return response()->json([
            'success' => true,
            'data'    => AddressResource::collection($addresses),
        ]);
    }

    public function store(AddressStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'آدرس با موفقیت ایجاد شد',
            'data'    => new AddressResource($address),
        ], 201);
    }

    public function update(AddressUpdateRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }

        $validated = $request->validated();

        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'آدرس به‌روزرسانی شد',
            'data'    => new AddressResource($address),
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'آدرس حذف شد',
        ]);
    }
}
