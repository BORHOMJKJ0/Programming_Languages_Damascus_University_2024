<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\Order\editItemRequest;
use App\Http\Resources\Order\Order_itemsResource;
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

    public function details($order_id)
    {
        $order = Order::where('id', $order_id)->first();
        if(!$order){
            return ResponseHelper::jsonResponse([], 'Order not found',404, false);
        }
        $order_details = $order->items;
        $data = [
            'order' => OrderResource::make($order),
            'order_details' => Order_itemsResource::collection($order_details),
        ];
        return ResponseHelper::jsonResponse($data, 'get order details successfully');
    }

    public function edit($item_id, editItemRequest $request)
    {
        $inputs = $request->validated();

        $item = Order_items::where('id', $item_id)->first();
        if(!$item){
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        if($item->order->user_id != auth()->id()){
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t edit this item, this item not for you',
                403,
                false
            );
        }

        $available_status = ['Pending', 'Preparing'];
        if(!in_array($item->item_status, $available_status)){
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t edit this item, item status is \''.$item->item_status.'\'',
                404,
                false
            );
        }
        $product = $item->product;
        if($inputs['quantity'] > $product->amount){
            return ResponseHelper::jsonResponse(
                [],
                'not available quantity',
                404,
                false
            );
        }
        $item->update([
            'quantity' => $inputs['quantity'],
            ]);

        return ResponseHelper::jsonResponse([], 'The item has been edited');
    }

    public function cancel($item_id)
    {
        $item = Order_items::where('id', $item_id)->first();
        if(!$item){
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        if($item->order->user_id != auth()->id()){
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t cancel this item, this item not for you',
                403,
                false
            );
        }

        $available_status = ['Pending', 'Preparing'];
        if(!in_array($item->item_status, $available_status)){
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t cancel this item, item status is \''.$item->item_status.'\'',
                404,
                false
            );
        }

        $item->update([
            'item_status' => 'Cancelled',
        ]);

        return ResponseHelper::jsonResponse([], 'The item has been cancelled');
    }

    public function deleteByCustomer($item_id)
    {
        $item = Order_items::where('id', $item_id)->first();
        if(!$item){
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        if($item->order->user_id != auth()->id()){
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t delete this item, this item not for you',
                403,
                false
            );
        }

        $available_status = ['Pending', 'Preparing', 'Not Available', 'Rejected', 'Delivered', 'Cancelled' ];
        if(!in_array($item->item_status, $available_status)){
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t delete this item, item status is \''.$item->item_status.'\'',
                404,
                false
            );
        }
        $item->delete();

        return ResponseHelper::jsonResponse([], 'The item has been deleted');
    }

    public function accept($item_id)
    {
        $item = Order_items::where('id', $item_id)->first();
        if(!$item){
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if($product->store->user_id != auth()->id())
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t accept this item, this item not for your store',
                403,
                false
            );
        }

        if($item->item_status != 'Pending')
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t accept this item, item status is \''.$item->item_status.'\'',
                404,
                false
            );
        }

        if($item->quantity > $product->amount)
        {
            $item->update([
                'item_status' => 'Not Available',
            ]);
            return ResponseHelper::jsonResponse(
                [],
                'not available quantity',
                404,
                false
            );
        }

        $product->update([
            'amount' => $product->amount - $item->quantity,
        ]);
        $item->update([
            'item_status' => 'Preparing',
        ]);

        return ResponseHelper::jsonResponse([], 'The item has been accepted');
    }

    public function reject($item_id)
    {
        $item = Order_items::where('id', $item_id)->first();
        if(!$item){
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if($product->store->user_id != auth()->id())
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t reject this item, this item not for your store',
                403,
                false
            );
        }

        if($item->item_status != 'Pending')
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t reject this item, item status is \''.$item->item_status.'\'',
                404,
                false
            );
        }

        $item->update([
            'item_status' => 'Rejected',
        ]);

        return ResponseHelper::jsonResponse([], 'The item has been rejected');
    }

    public function ship($item_id)
    {
        $item = Order_items::where('id', $item_id)->first();
        if(!$item){
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if($product->store->user_id != auth()->id())
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t ship this item, this item not for your store',
                403,
                false
            );
        }

        if($item->item_status != 'Preparing')
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t ship this item, item status is \''.$item->item_status.'\'',
                404,
                false
            );
        }

        $item->update([
            'item_status' => 'Shipped',
        ]);

        return ResponseHelper::jsonResponse([], 'The item has been shipped');
    }

    public function deliver($item_id)
    {
        $item = Order_items::where('id', $item_id)->first();
        if(!$item){
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if($product->store->user_id != auth()->id())
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t deliver this item, this item not for your store',
                403,
                false
            );
        }

        if($item->item_status != 'Shipped')
        {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t deliver this item, item status is \''.$item->item_status.'\'',
                404,
                false
            );
        }

        $item->update([
            'item_status' => 'Delivered',
        ]);

        return ResponseHelper::jsonResponse([], 'The item has been Delivered');
    }

}
