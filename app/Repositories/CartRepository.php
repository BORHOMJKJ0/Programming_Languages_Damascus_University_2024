<?php

namespace App\Repositories;

use App\Models\Cart\Cart;
use App\Traits\Lockable;

class CartRepository
{
    use Lockable;

    public function create()
    {
        $data = [
            'user_id' => auth()->id(),
        ];

        return $this->lockForCreate(function () use ($data) {
            return Cart::create($data);
        });
    }

    public function update(Cart $cart)
    {

        return $this->lockForUpdate(Cart::class, $cart->id, function ($lockedCart) {
            $lockedCart->cart_items()->delete();
            $data = [];
            $lockedCart->update($data);

            return $lockedCart;
        });
    }

    public function delete(Cart $cart)
    {
        return $this->lockForDelete(Cart::class, $cart->id, function ($lockedCart) {
            return $lockedCart->delete();
        });
    }
}
