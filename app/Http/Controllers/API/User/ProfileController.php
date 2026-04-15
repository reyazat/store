<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
        ]);
        
        $request->user()->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'پروفایل به‌روزرسانی شد',
            'data' => $request->user(),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);
        
        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز عبور فعلی اشتباه است',
            ], 400);
        }
        
        $request->user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'رمز عبور با موفقیت تغییر کرد',
        ]);
    }
}
