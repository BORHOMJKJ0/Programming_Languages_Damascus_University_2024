<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserService
{
    public function refresh_Token()
    {
        try {
            $new_token = JWTAuth::parseToken()->refresh();
        } catch (TokenInvalidException $ex) {
            return ResponseHelper::jsonResponse([], 'Invalid token', 401, false);
        } catch (TokenExpiredException $ex) {
            return ResponseHelper::jsonResponse([], 'Expired token', 401, false);
        } catch (JWTException $ex) {
            return ResponseHelper::jsonResponse([], 'token is missing', 401, false);
        }
        $data = [
            'token' => $new_token,
        ];

        return ResponseHelper::jsonResponse($data, 'Token refreshed');
    }

    public function update_role($user_id, $new_role)
    {
        $user = User::find($user_id);
        $user->role->update([
            'role' => $new_role,
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $inputs = $request->all();

        $inputs['password'] = Hash::make($inputs['password']);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $inputs['image'] = $path;
        }
        $role = Role::create([
            'role' => 'user',
        ]);
        $inputs['role_id'] = $role->id;

        $user = User::create($inputs);

        $data = [
            'user' => UserResource::make($user),
        ];

        return ResponseHelper::jsonResponse($data, 'Register successfully', 201);
    }

    public function register_for_guest(RegisterRequest $request, $guest_id): JsonResponse
    {
        $inputs = $request->all();

        $inputs['password'] = Hash::make($inputs['password']);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $inputs['image'] = $path;
        }

        $user = User::where('id', $guest_id)->first();
        if (! $user) {
            return ResponseHelper::jsonResponse([], 'User not found', 404, false);
        }
        if ($user->role->role != 'guest') {
            return ResponseHelper::jsonResponse([], 'registered already', 404, false);
        }
        $user->update($inputs);
        $user->save();

        $data = [
            'user' => UserResource::make($user),
        ];

        $this->update_role($user->id, 'user');

        return ResponseHelper::jsonResponse($data, 'Register successfully', 201);
    }

    public function getStarted()
    {
        $role = Role::create([
            'role' => 'guest',
        ]);
        $guest = User::create([
            'role_id' => $role->id,
        ]);

        $data = [
            'guest_id' => $guest->id,
        ];

        return ResponseHelper::jsonResponse($data, 'Get started successfully', 201);
    }

    public function login(LoginRequest $request)
    {
        $inputs = $request->all();

        if (isset($inputs['remember_me']) && $inputs['remember_me']) {
            JWTAuth::factory()->setTTL(60 * 24 * 30 * 3);
        }

        $credentials = $request->only('mobile_number', 'password');
        $token = JWTAuth::attempt($credentials);

        if (! $token) {
            return ResponseHelper::jsonResponse([], 'mistake password', 401, false);
        }

        $user = JWTAuth::user();

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
        JWTAuth::invalidate($token);

        return ResponseHelper::jsonResponse([], 'Logged out successfully!');
    }

    public function getProfile()
    {
        $user = JWTAuth::user();
        $data = [
            'user' => UserResource::make($user),
        ];

        return ResponseHelper::jsonResponse($data, 'Get profile successfully');
    }

    public function updateProfile(Request $request)
    {
        $inputs = $request->all();

        $user = JWTAuth::user();

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
        $user = JWTAuth::user();

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
