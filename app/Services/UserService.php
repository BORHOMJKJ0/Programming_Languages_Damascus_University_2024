<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $inputs = $request->all();

        $inputs['password'] = Hash::make($inputs['password']);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $inputs['image'] = $path;
        }

        $user = User::where('id', $inputs['id'])->first();
        $user->update($inputs);
        $user->save();

        $data = [
            'user' => UserResource::make($user),
        ];

        $user->role->update([
            'role' => 'user',
        ]);

        return ResponseHelper::jsonResponse($data, 'Register successfully', 201);
    }

    public function getStarted()
    {
        $guest = User::create();
        Role::create([
            'user_id' => $guest->id,
            'role' => 'guest',
        ]);
        $data = [
            'guest_id' => $guest->id,
        ];

        return ResponseHelper::jsonResponse($data, 'Get started successfully', 201);
    }

    public function login(LoginRequest $request)
    {
        $inputs = $request->all();

        $credentials = $request->only('mobile_number', 'password');
        $token = Auth::guard('api')->attempt($credentials);

        if (! $token) {
            return ResponseHelper::jsonResponse([], 'mistake password', 401, false);
        }

        $user = Auth::guard('api')->user();

        $user->update([
            'fcm_token' => $inputs['fcm_token'],
        ]);

        $data = [
            'user' => UserResource::make($user),
            'token' => $token,
        ];

        return ResponseHelper::jsonResponse($data, 'Logged in successfully');
    }

    public function logout(Request $request)
    {
        $token = $request->header('Authorization');
        Auth::guard('api')->invalidate($token);

        return ResponseHelper::jsonResponse([], 'Logged out successfully!');
    }

    public function getProfile()
    {
        $user = Auth::guard('api')->user();
        $data = [
            'user' => UserResource::make($user),
        ];

        return ResponseHelper::jsonResponse($data, 'Get profile successfully');
    }

    public function updateProfile(Request $request)
    {
        $inputs = $request->all();

        $user = auth()->user();

        if ($request->hasFile('image')) {
            if ($request->image && Storage::disk('public')->exists($request->image)) {
                $path = $inputs['image']->store('images', 'public');
                $inputs['image'] = $path;
            }
        }
        if ($request->hasFile('image')) {
            if ($user->image) {
                if (Storage::disk('public')->exists($user->image)) {
                    Storage::disk('public')->delete($user->image);
                }
            }
            $path = $inputs['image']->store('images', 'public');
            $inputs['image'] = $path;
        }

        $user->update($inputs);

        $data = [
            'user' => UserResource::make($user),
        ];

        return ResponseHelper::jsonResponse($data, 'profile updated successfully', 201);
    }

    public function resetPassword(Request $request)
    {
        $inputs = $request->all();
        $user = auth()->user();

        if (! Hash::check($inputs['old_password'], $user->password)) {
            return ResponseHelper::jsonResponse([], 'old password is incorrect', 401, false);
        }

        $inputs['new_password'] = Hash::make($inputs['new_password']);
        $user->update([
            'password' => $inputs['new_password'],
        ]);

        return ResponseHelper::jsonResponse([], 'Password reset successfully');
    }
}
