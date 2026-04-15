<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
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
            'data' => $addresses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'country' => 'nullable|string|max:100',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'required|string',
            'postal_code' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);
        
        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }
        
        $address = $request->user()->addresses()->create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'آدرس با موفقیت ایجاد شد',
            'data' => $address,
        ], 201);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز',
            ], 403);
        }
        
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email',
            'country' => 'nullable|string|max:100',
            'province' => 'sometimes|string|max:100',
            'city' => 'sometimes|string|max:100',
            'address' => 'sometimes|string',
            'postal_code' => 'sometimes|string|max:20',
            'notes' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);
        
        if ($validated['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }
        
        $address->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'آدرس به‌روزرسانی شد',
            'data' => $address,
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
