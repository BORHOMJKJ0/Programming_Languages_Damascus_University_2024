<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Models\Order\Order;
use App\Models\Order\Order_items;
use Illuminate\Http\JsonResponse;

class OrderService
{
    public function findOrderById($order_id)
    {
        return Order::find($order_id);
    }

    public function placeOrder(): JsonResponse
    {
        $cart = auth()->user()->cart;
        $cart_items = $cart->cart_items;

        $order_ids = [];
        foreach ($cart_items as $cart_item) {
            $product = $cart_item->product;
            if (! isset($order_ids[$product->store_id])) {
                $order = Order::create([
                    'user_id' => auth()->id(),
                ]);
                $order_ids[$product->store_id] = $order->id;
            } else {
                $order_id = $order_ids[$product->store_id];
                $order = $this->findOrderById($order_id);
            }

            $order_items = Order_items::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $cart_item->quantity,
                'price' => $product->price * $cart_item->quantity,
            ]);

            $order->update([
                'total_amount' => $order->total_amount + $order_items->quantity,
                'total_price' => $order->total_price + $order_items->price,
            ]);
        }

        return ResponseHelper::jsonResponse([], 'The order has been placed');
    }
}
