<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Cart\CartResource;
use App\Models\Cart\Cart;
use App\Models\User\User;
use App\Repositories\CartRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\Exceptions\HttpResponseException;

class CartService
{
    use AuthTrait;

    protected $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function getCartByUserId(?User $user)
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();
        }
        $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();

        $cart = Cart::where('user_id', $user->id)->first();
        if (! $cart) {
            if (! $this->checkSuperAdmin()) {
                return ResponseHelper::jsonResponse([], 'No cart found for the user Please login.', 404, false);
            } else {
                return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : please create cart for this user', 404, false);
            }
        }
        $data = ['Cart' => CartResource::make($cart)];

        return ResponseHelper::jsonResponse($data, 'Cart retrieved successfully!');
    }

    public function createCart(?User $user)
    {
        if (! $this->checkSuperAdmin()) {
            $this->checkGuest();
        }
        $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();
        $carts = Cart::where('user_id', $user->id)->get();
        if ($carts->isEmpty()) {
            $cart = $this->cartRepository->create($user->id);
            $data = [
                'Cart' => CartResource::make($cart),
            ];

            return ResponseHelper::jsonResponse($data, 'Cart created successfully!');
        }

        return ResponseHelper::jsonResponse([], 'Cart created before', 403);
    }

    public function updateCart(?User $user)
    {
        try {
            $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();
            $cart = Cart::where('user_id', $user->id)->first();

            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkOwnership($cart, 'Cart', 'update');

            }

            if (! $cart) {
                if (! $this->checkSuperAdmin()) {
                    return ResponseHelper::jsonResponse([], 'No cart found for the user Please login.', 404, false);
                } else {
                    return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : please create cart for this user', 404, false);
                }
            }
            $cart = $this->cartRepository->update($cart);
            $data = [
                'Cart' => CartResource::make($cart),
            ];

            $response = ResponseHelper::jsonResponse($data, 'Cart updated successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }

    public function deleteCart(?User $user)
    {
        try {
            $user = $this->checkSuperAdmin() ? $user ?? auth()->user() : auth()->user();
            $cart = Cart::where('user_id', $user->id)->first();
            if (! $this->checkSuperAdmin()) {
                $this->checkGuest();
                $this->checkOwnership($cart, 'Cart', 'delete');

            }
            if (! $cart) {
                if (! $this->checkSuperAdmin()) {
                    return ResponseHelper::jsonResponse([], 'No cart found for the user Please login.', 404, false);
                } else {
                    return ResponseHelper::jsonResponse([], 'Mr.SuperAdmin : please create cart for this user', 404, false);
                }
            }
            $this->cartRepository->delete($cart);
            $response = ResponseHelper::jsonResponse([], 'Cart deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }
}
