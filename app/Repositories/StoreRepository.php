<?php

namespace App\Repositories;

use App\Models\Store;
use App\Traits\Lockable;

class StoreRepository
{
    use Lockable;

    public function getAll($items,$page)
    {
        return Store::where('user_id', auth()->id())->paginate($items, ['*'], 'page', $page);
    }

    public function orderBy($column, $direction, $page, $items)
    {
        return Store::where('user_id', auth()->id())->orderBy($column, $direction)->paginate($items, ['*'], 'page', $page);
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
