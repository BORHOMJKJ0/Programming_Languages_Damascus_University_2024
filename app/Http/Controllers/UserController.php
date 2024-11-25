<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(RegisterRequest $request)
    {
        return $this->userService->register($request);
    }

    public function getStarted()
    {
        return $this->userService->getStarted();
    }

    public function login(LoginRequest $request)
    {
        return $this->userService->login($request);
    }
    public function logout(Request $request)
    {
        return $this->userService->logout($request);
    }

    public function getProfile()
    {
        return $this->userService->getProfile();
    }

    public function updateProfile(Request $request)
    {
        $inputs = $request->all();

        $user = auth()->user();

        if($request->hasFile('image')){
            if($request->image && Storage::disk('public')->exists($request->image)){
                $path = $inputs['image']->store('images', 'public');
                $inputs['image'] = $path;
            }
        }
        if ($request->hasFile('image')) {
            if($user->image){
                if(Storage::disk('public')->exists($user->image)){
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

        return ResponseHelper::jsonResponse($data, 'profile updated successfully',201);
    }
}
