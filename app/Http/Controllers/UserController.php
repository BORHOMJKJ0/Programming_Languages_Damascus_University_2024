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
}
