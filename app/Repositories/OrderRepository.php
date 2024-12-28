<?php

namespace App\Repositories;

use App\Models\Order\Order;
use App\Traits\Lockable;

class OrderRepository
{
    use Lockable;

    public function getAllOrdersByUserId($user_id = null)
    {
        if (!$user_id)
            return auth()->user()->orders;
        return Order::where('user_id', $user_id)->get();
    }

    public function getOrdersArchivedByUserId($user_id = null)
    {
        if(!$user_id) $user_id = auth()->id();
        return Order::onlyTrashed()->where('user_id', $user_id)->get();
    }

    public function getOrdersArchivedByStoreId($store_id)
    {
        return Order::onlyTrashed()->where('store_id', $store_id)->get();
    }

    public function getAllOrdersByStoreId($store_id)
    {
        return Order::where('store_id', $store_id)->get();
    }

    public function getOrderDetails(Order $order)
    {
        return $order->items;
    }

    public function getOrderById($order_id)
    {
        return Order::where('id', $order_id)->first();
    }
    public function createNewOrder(array $data)
    {
        return Order::create($data);
    }
    public function updateOrder(Order $order, array $data)
    {
        $order->update($data);
    }

    public function updateOrderStatus(Order $order, $order_status)
    {
        return $order->update(['order_status' => $order_status]);
    }

    public function addToArchive(Order $order)
    {
        $order->delete();
    }

    public function deleteOrder(Order $order)
    {
        $order->forceDelete();
    }

    public function refreshOrderStatus(Order $order)
    {
        $items = $order->items;
        if ($items->isEmpty()) {
            $this->deleteOrder($order);
        }

        $status_of_items = [];
        foreach ($items as $item) {
            $status_of_items[] = $item->item_status;
        }
        $status_of_items = array_unique($status_of_items);

        if (count($status_of_items) == 1) {
            $this->updateOrderStatus($order, $status_of_items[0]);
        } else {
            $processing = ['Pending', 'Preparing', 'Shipped'];
            foreach ($status_of_items as $status_of_item) {
                if (in_array($status_of_item, $processing)) {
                    $this->updateOrderStatus($order, 'Processing');
                    return;
                }
            }
            $this->updateOrderStatus($order, 'Completed');
        }
        if(in_array($order->order_status, ['Delivered', 'Completed', 'Cancelled', 'Rejected', 'Not Available']))
            $this->addToArchive($order);
    }
}
