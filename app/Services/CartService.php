<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Cart\CartResource;
use App\Models\Cart\Cart;
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

    public function getCartById()
    {
        $this->checkGuest('Cart', 'perform');
        $cart = Cart::where('user_id', auth()->id())->first();
        if (! $cart) {
            return ResponseHelper::jsonResponse([], 'No cart found for the user Please login.', 404, false);
        }
        $data = ['Cart' => CartResource::make($cart)];

        return ResponseHelper::jsonResponse($data, 'Cart retrieved successfully!');
    }

    public function createCart()
    {
        $this->checkGuest('Cart', 'create');
        $carts = Cart::where('user_id', auth()->id())->get();
        if ($carts->isEmpty()) {
            $cart = $this->cartRepository->create();
            $data = [
                'Cart' => CartResource::make($cart),
            ];

            return ResponseHelper::jsonResponse($data, 'Cart created successfully!');
        }

        return ResponseHelper::jsonResponse([], 'Cart created before');
    }

    public function updateCart()
    {
        try {
            $this->checkGuest('Cart', 'update');
            $cart = Cart::where('user_id', auth()->id())->first();
            $this->checkOwnership($cart, 'Cart', 'update');
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

    public function deleteCart()
    {
        try {
            $this->checkGuest('Cart', 'delete');
            $cart = Cart::where('user_id', auth()->id())->first();
            $this->checkOwnership($cart, 'Cart', 'delete');
            $this->cartRepository->delete($cart);
            $response = ResponseHelper::jsonResponse([], 'Cart deleted successfully!');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;
    }
}
