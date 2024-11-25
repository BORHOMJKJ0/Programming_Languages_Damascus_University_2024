<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
public function register(RegisterRequest $request) : JsonResponse
{
    $inputs = $request->all();

    $inputs['password'] = Hash::make($inputs['password']);
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('images', 'public');
        $inputs['image'] = $path;
    }

    $user =  User::create($inputs);

    Auth::guard('api')->login($user);

    $data = [
        'user' => UserResource::make($user),
    ];

    return ResponseHelper::jsonResponse($data, 'Register successfully');
}
}
