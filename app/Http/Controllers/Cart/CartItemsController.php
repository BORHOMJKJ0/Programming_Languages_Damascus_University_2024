<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ValidateUserIdRequest;
use App\Models\Cart\Cart_items;
use App\Services\Cart_Items_Service;
use Illuminate\Http\JsonResponse;

class CartItemsController extends Controller
{
    protected Cart_Items_Service $cart_items_Service;

    public function __construct(Cart_Items_Service $cart_items_items_Service)
    {
        $this->cart_items_Service = $cart_items_items_Service;
    }

    public function index(ValidateUserIdRequest $request): JsonResponse
    {
        return $this->cart_items_Service->getAllCart_items($request);
    }

    public function orderBy($column, $direction, ValidateUserIdRequest $request): JsonResponse
    {
        return $this->cart_items_Service->getCart_items_OrderedBy($column, $direction, $request);
    }

    public function store(ValidateUserIdRequest $request): JsonResponse
    {
        return $this->cart_items_Service->createCart_items($request->all(), $request);
    }

    public function update(Cart_items $cart_item, ValidateUserIdRequest $request): JsonResponse
    {
        return $this->cart_items_Service->updateCart_items($cart_item, $request->all());
    }

    public function show(Cart_items $cart_item): JsonResponse
    {
        return $this->cart_items_Service->getCart_itemById($cart_item);
    }

    public function destroy(Cart_items $cart_item): JsonResponse
    {
        return $this->cart_items_Service->deleteCart_items($cart_item);
    }
}
