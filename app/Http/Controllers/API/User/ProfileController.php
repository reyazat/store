<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Resources\Profile\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new ProfileResource($request->user()),
        ]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'پروفایل به‌روزرسانی شد',
            'data'    => new ProfileResource($user),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز عبور فعلی اشتباه است',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'رمز عبور با موفقیت تغییر یافت',
        ]);
    }
}
