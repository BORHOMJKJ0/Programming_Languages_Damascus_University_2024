<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order\Order;
use App\Models\Order\Order_items;
use App\Models\Store\Store;
use App\Repositories\CartRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\JsonResponse;

class OrderService
{
    use AuthTrait;
    protected $cartRepository;


    public function __construct(CartRepository $cartRepository){
        $this->cartRepository = $cartRepository;
    }
    public function findOrderById($order_id)
    {
        return Order::find($order_id);
    }

    public function placeOrder(): JsonResponse
    {
        $cart = auth()->user()->cart;
        if($cart->cart_items->isEmpty()){
            return ResponseHelper::jsonResponse([], 'Your cart is empty',400, false);
        }
        $cart_items = $cart->cart_items;

        $order_ids = [];
        foreach ($cart_items as $cart_item) {
            $product = $cart_item->product;
            if (! isset($order_ids[$product->store_id])) {
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'store_id' => $product->store_id,
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

            $this->cartRepository->update($cart);
        }

        return ResponseHelper::jsonResponse([], 'The order has been placed');
    }

    public function getAllMyOrders()
    {
        $user_id = auth()->id();
        $orders = Order::where('user_id', $user_id)->get();

        $data = [
            'orders' => OrderResource::collection($orders),
        ];

        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

    public function getAllStoreOrders($store_id)
    {
        $store = Store::where('id', $store_id)->first();
        $this->checkOwnership($store, 'Store', 'show orders of ');

        $orders = Order::where('store_id', $store->id)->get();

        $data = [
            'orders' => OrderResource::collection($orders),
        ];
        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

}
