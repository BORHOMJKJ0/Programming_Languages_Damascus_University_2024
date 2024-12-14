<?php

namespace App\Repositories;

use App\Models\Store\Store;
use App\Traits\Lockable;

class StoreRepository
{
    use Lockable;

    public function getAll($items)
    {
        return Store::paginate($items);
    }

    public function findByUserId($user_id = null)
    {
        $userId = $user_id ?? auth()->id();

        return Store::where('user_id', $userId)->get();
    }

    public function orderBy($column, $direction, $items)
    {
        return Store::orderBy($column, $direction)->paginate($items);
    }

    public function create(array $data)
    {
        return $this->lockForCreate(function () use ($data) {
            return Store::create($data);
        });
    }

    public function update(Store $store, array $data)
    {
        return $this->lockForUpdate(Store::class, $store->id, function ($lockedStore) use ($data) {
            $lockedStore->update($data);

            return $lockedStore;
        });
    }

    public function delete(Store $store)
    {
        return $this->lockForDelete(Store::class, $store->id, function ($lockedStore) {
            return $lockedStore->delete();
        });
    }
}
