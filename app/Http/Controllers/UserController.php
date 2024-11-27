<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
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

    public function updateProfile(Request $request)
    {
        return $this->userService->updateProfile($request);
    }

    public function resetPassword(Request $request)
    {
        return $this->userService->resetPassword($request);
    }
}
