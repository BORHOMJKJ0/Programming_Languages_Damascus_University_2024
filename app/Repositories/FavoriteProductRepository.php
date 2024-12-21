<?php

namespace App\Repositories;

use App\Models\User\User;
use App\Traits\Lockable;

class FavoriteProductRepository
{
    use Lockable;

    public function getAll($user, $items)
    {
        return $user->favoriteProducts()->with(['category'])->paginate($items);
    }

    public function create($user, $product)
    {
        return $this->lockForCreate(function () use ($user, $product) {
            return $user->favoriteProducts()->attach($product->id);
        });
    }

    public function delete($user, $product)
    {
        return $this->lockForDelete(User::class, $user->id, function ($user) use ($product) {
            return $user->favoriteProducts()->detach($product->id);
        });
    }
}
