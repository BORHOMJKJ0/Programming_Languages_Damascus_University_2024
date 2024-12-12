<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function store(?User $user = null): JsonResponse
    {
        return $this->cartService->createCart($user);
    }

    public function update(?User $user = null): JsonResponse
    {
        return $this->cartService->updateCart($user);
    }

    public function show(?User $user = null): JsonResponse
    {
        return $this->cartService->getCartByUserId($user);
    }

    public function destroy(?User $user = null): JsonResponse
    {
        return $this->cartService->deleteCart($user);
    }
}
