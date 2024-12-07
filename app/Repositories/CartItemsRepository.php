<?php

namespace App\Repositories;

use App\Models\Cart\Cart;
use App\Models\Cart\Cart_items;
use App\Traits\AuthTrait;
use App\Traits\Lockable;

class CartItemsRepository
{
    use AuthTrait,Lockable;

    public function getAll($items, $page)
    {
        $this->checkCart();

        return Cart_items::whereHas('cart', function ($query) {
            $query->where('user_id', auth()->id());
        })->paginate($items, ['*'], 'page', $page);
    }

    public function orderBy($column, $direction, $page, $items)
    {
        $this->checkCart();

        return Cart_items::whereHas('cart', function ($query) {
            $query->where('user_id', auth()->id());
        })->orderBy($column, $direction)->paginate($items, ['*'], 'page', $page);
    }

    public function create(array $data)
    {
        $this->checkCart();
        $cart = Cart::where('user_id', auth()->id())->first();
        $data['cart_id'] = $cart->id;

        return $this->lockForCreate(function () use ($data) {
            return Cart_items::create($data);
        });
    }

    public function update(Cart_items $cart_items, array $data)
    {
        $this->checkCart();

        return $this->lockForUpdate(Cart_items::class, $cart_items->id, function ($locked_Cart_items) use ($data) {
            $locked_Cart_items->update($data);

            return $locked_Cart_items;
        });
    }

    public function delete(Cart_items $cart_items)
    {
        $this->checkCart();

        return $this->lockForDelete(Cart_items::class, $cart_items->id, function ($locked_Cart_items) {
            return $locked_Cart_items->delete();
        });
    }
}
