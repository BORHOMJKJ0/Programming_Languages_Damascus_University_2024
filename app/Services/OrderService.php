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
use Illuminate\Http\Request;

class OrderService
{
    use AuthTrait;

    protected $cartRepository;

    protected $fcmService;

    public function __construct(CartRepository $cartRepository, FcmService $fcmService)
    {
        $this->cartRepository = $cartRepository;
        $this->fcmService = $fcmService;
    }

    public function findOrderById($order_id)
    {
        return Order::find($order_id);
    }

    public function refreshOrderStatus($order)
    {
        $items = $order->items;
        if ($items->isEmpty()) {
            $order->delete();
        }

        $status_of_items = [];
        foreach ($items as $item) {
            $status_of_items[] = $item->item_status;
        }
        $status_of_items = array_unique($status_of_items);

        if (count($status_of_items) == 1) {
            $order->update([
                'order_status' => $status_of_items[0],
            ]);
        } else {
            $processing = ['Pending', 'Preparing', 'Shipped'];
            foreach ($status_of_items as $status_of_item) {
                if (in_array($status_of_item, $processing)) {
                    $order->update([
                        'order_status' => 'Processing',
                    ]);

                    return;
                }
            }
            $order->update([
                'order_status' => 'Completed',
            ]);
        }
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $cart = auth()->user()->cart;
        if ($cart->cart_items->isEmpty()) {
            return ResponseHelper::jsonResponse([], 'Your cart is empty', 400, false);
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
        foreach ($order_ids as $order_id) {
            $order = $this->findOrderById($order_id);
            $this->fcmService->notifyPlaceOrder($order, $request->header('lang', 'en'));
        }

        return ResponseHelper::jsonResponse([], 'The order has been placed');
    }

    public function getAllMyOrders()
    {
        $orders = Order::where('user_id', auth()->id())->get();

        $data = [
            'orders' => OrderResource::collection($orders),
        ];

        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

    public function getAllStoreOrders(Store $store)
    {
        $this->checkOwnership($store, 'Store', 'show orders of ');

        $orders = Order::where('store_id', $store->id)->get();

        $data = [
            'orders' => OrderResource::collection($orders),
        ];

        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

    public function details(Order $order)
    {
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
        if (! $item) {
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        if ($item->order->user_id != auth()->id()) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t edit this item, this item not for you',
                403,
                false
            );
        }

        $available_status = ['Pending', 'Preparing'];
        if (! in_array($item->item_status, $available_status)) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t edit this item, item status is \''.$item->item_status.'\'',
                403,
                false
            );
        }
        $product = $item->product;
        if ($inputs['quantity'] > $product->amount) {
            return ResponseHelper::jsonResponse(
                [],
                'not available quantity',
                404,
                false
            );
        }
        $old_quantity = $item->quantity;
        $new_quantity = $inputs['quantity'];
        $item->order->update([
            'total_amount' => $item->order->total_amount - $old_quantity + $new_quantity,
            'total_price' => ($item->order->total_price - $item->price) + $new_quantity * $item->product->price,
        ]);
        $item->update([
            'quantity' => $new_quantity,
            'price' => $new_quantity * $item->product->price,
        ]);

        $this->fcmService->notifyٍStoreItem($item, 'update', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been edited');
    }

    public function deleteByCustomer($item_id, Request $request)
    {
        $item = Order_items::where('id', $item_id)->first();
        if (! $item) {
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        if ($item->order->user_id != auth()->id()) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t delete this item, this item not for you',
                403,
                false
            );
        }

        $available_status = ['Pending', 'Preparing', 'Not Available', 'Rejected', 'Cancelled'];
        if (! in_array($item->item_status, $available_status)) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t delete this item, item status is \''.$item->item_status.'\'',
                403,
                false
            );
        }
        $order = $item->order;
        $order->update([
            'total_amount' => $order->total_amount - $item->quantity,
            'total_price' => $order->total_price - $item->price,
        ]);
        $this->fcmService->notifyٍStoreItem($item, 'delete', $request->header('lang', 'en'));
        $item->delete();
        $this->refreshOrderStatus($order);

        return ResponseHelper::jsonResponse([], 'The item has been deleted');
    }

    public function accept($item_id, Request $request)
    {
        $item = Order_items::where('id', $item_id)->first();
        if (! $item) {
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if ($product->store->user_id != auth()->id()) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t accept this item, this item not for your store',
                403,
                false
            );
        }

        if ($item->item_status != 'Pending') {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t accept this item, item status is \''.$item->item_status.'\'',
                403,
                false
            );
        }

        if ($item->quantity > $product->amount) {
            $item->update([
                'item_status' => 'Not Available',
            ]);

            $this->fcmService->notifyCustomerItem($item, 'not available', $request->header('lang', 'en'));

            return ResponseHelper::jsonResponse(
                [],
                'not available quantity',
                200,
                false
            );
        }

        $product->update([
            'amount' => $product->amount - $item->quantity,
        ]);
        $item->update([
            'item_status' => 'Preparing',
        ]);
        $this->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'accept', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been accepted');
    }

    public function reject($item_id, Request $request)
    {
        $item = Order_items::where('id', $item_id)->first();
        if (! $item) {
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if ($product->store->user_id != auth()->id()) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t reject this item, this item not for your store',
                403,
                false
            );
        }

        if ($item->item_status != 'Pending') {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t reject this item, item status is \''.$item->item_status.'\'',
                403,
                false
            );
        }
        if ($item->quantity > $product->amount) {
            $item->update([
                'item_status' => 'Not Available',
            ]);

            $this->fcmService->notifyCustomerItem($item, 'not available', $request->header('lang', 'en'));

            return ResponseHelper::jsonResponse(
                [],
                'The item has been rejected, the reason is not available quantity',
                200,
                false
            );
        }

        $item->update([
            'item_status' => 'Rejected',
        ]);
        $item->order->update([
            'total_amount' => $item->order->total_amount - $item->quantity,
            'total_price' => $item->order->total_price - $item->price,
        ]);
        $this->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'reject', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been rejected');
    }

    public function ship($item_id, Request $request)
    {
        $item = Order_items::where('id', $item_id)->first();
        if (! $item) {
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if ($product->store->user_id != auth()->id()) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t ship this item, this item not for your store',
                403,
                false
            );
        }

        if ($item->item_status != 'Preparing') {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t ship this item, item status is \''.$item->item_status.'\'',
                403,
                false
            );
        }

        $item->update([
            'item_status' => 'Shipped',
        ]);
        $this->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'ship', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been shipped');
    }

    public function deliver($item_id, Request $request)
    {
        $item = Order_items::where('id', $item_id)->first();
        if (! $item) {
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if ($product->store->user_id != auth()->id()) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t deliver this item, this item not for your store',
                403,
                false
            );
        }

        if ($item->item_status != 'Shipped') {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t deliver this item, item status is \''.$item->item_status.'\'',
                403,
                false
            );
        }

        $item->update([
            'item_status' => 'Delivered',
        ]);
        $this->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'deliver', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been Delivered');
    }

    public function cancelByStore($item_id, Request $request)
    {
        $item = Order_items::where('id', $item_id)->first();
        if (! $item) {
            return ResponseHelper::jsonResponse(
                [],
                'Item not found',
                404,
                false
            );
        }

        $product = $item->product;
        if ($product->store->user_id != auth()->id()) {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t cancel this item, this item not for your store',
                403,
                false
            );
        }

        if ($item->item_status != 'Preparing') {
            return ResponseHelper::jsonResponse(
                [],
                'Can\'t cancel this item, item status is \''.$item->item_status.'\'',
                403,
                false
            );
        }

        $item->update([
            'item_status' => 'Cancelled',
        ]);
        $item->order->update([
            'total_amount' => $item->order->total_amount - $item->quantity,
            'total_price' => $item->order->total_price - $item->price,
        ]);
        $this->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'cancel', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been Cancelled');
    }
}
