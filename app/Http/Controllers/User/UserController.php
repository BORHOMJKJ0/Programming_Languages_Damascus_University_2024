<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function refresh_token()
    {
        return $this->userService->refresh_token();
    }

    public function register(RegisterRequest $request)
    {
        return $this->userService->register($request);
    }

    public function register_for_guest(RegisterRequest $request, $guest_id)
    {
        return $this->userService->register_for_guest($request, $guest_id);
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

    public function updateProfile(UpdateProfileRequest $request)
    {
        return $this->userService->updateProfile($request);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->userService->resetPassword($request);
    }
}
