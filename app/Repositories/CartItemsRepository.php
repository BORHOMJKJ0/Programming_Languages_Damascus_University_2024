<?php

namespace App\Repositories;

use App\Models\Cart\Cart_items;
use App\Traits\Lockable;

class CartItemsRepository
{
    use Lockable;

    public function getAll($items, $user_id)
    {
        return Cart_items::whereHas('cart', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->paginate($items);
    }

    public function orderBy($column, $direction, $items, $user_id)
    {
        return Cart_items::whereHas('cart', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->orderBy($column, $direction)->paginate($items);
    }

    public function create(array $data)
    {
        return $this->lockForCreate(function () use ($data) {
            return Cart_items::create($data);
        });
    }

    public function update(Cart_items $cart_items, array $data)
    {
        return $this->lockForUpdate(Cart_items::class, $cart_items->id, function ($locked_Cart_items) use ($data) {
            $locked_Cart_items->update($data);

            return $locked_Cart_items;
        });
    }

    public function delete(Cart_items $cart_items)
    {
        return $this->lockForDelete(Cart_items::class, $cart_items->id, function ($locked_Cart_items) {
            return $locked_Cart_items->delete();
        });
    }
}
