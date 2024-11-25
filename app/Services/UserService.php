<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

    $user =  User::where('id',$inputs['id'])->first();
    $user->update($inputs);
    $user->save();

    $data = [
        'user' => UserResource::make($user),
    ];

    $user->role->update([
        'role' => 'user'
    ]);

    return ResponseHelper::jsonResponse($data, 'Register successfully',201);
}

public function getStarted()
{
    $guest = User::create();
    Role::create([
        'user_id' => $guest->id,
        'role' => 'guest'
    ]);
    $data = [
        'guest_id' => $guest->id,
    ];
    return ResponseHelper::jsonResponse($data, 'Get started successfully',201);
}
}
