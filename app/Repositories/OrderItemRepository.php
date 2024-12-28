<?php

namespace App\Repositories;

use App\Models\Order\Order_item;
use App\Traits\Lockable;

class OrderItemRepository
{
    use Lockable;

    public function createNewItem(array $data)
    {
        return Order_item::create($data);
    }

    public function updateItem(Order_item $order_item, array $data)
    {
        return $order_item->update($data);
    }

    public function updateItemStatus(Order_item $item, $item_status)
    {
        return $item->update(['item_status' => $item_status]);
    }

    public function getItemsById($item_id)
    {
        return Order_item::where('id', $item_id)->first();
    }
}
