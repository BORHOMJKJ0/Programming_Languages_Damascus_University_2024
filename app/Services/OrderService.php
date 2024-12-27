<?php

namespace App\Services;

use App\Helpers\ResponseHelper;
use App\Http\Requests\Order\editItemRequest;
use App\Http\Resources\Order\Order_itemsResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order\Order;
use App\Models\Order\Order_item;
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

            $order_items = Order_item::create([
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

    public function edit(Order_item $item, editItemRequest $request)
    {
        $inputs = $request->validated();
        $this->checkOwnership($item->order, 'Order', 'edit an item from');
        $available_status = ['Pending', 'Preparing'];
        $this->checkIfCanChangeItemStatus($item, $available_status, 'edit');

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
    public function deleteByCustomer(Order_item $item, Request $request)
    {
        $this->checkOwnership($item->order, 'Order', 'delete an item from');
        $available_status = ['Pending', 'Preparing', 'Not Available', 'Rejected', 'Cancelled'];
        $this->checkIfCanChangeItemStatus($item, $available_status, 'delete');

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

    public function accept(Order_item $item, Request $request)
    {
        $this->checkOwnershipForItem($item, 'accept');
        $this->checkIfCanChangeItemStatus($item, ['Pending'], 'accept');

        $product = $item->product;
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

    public function reject(Order_item $item, Request $request)
    {
        $this->checkOwnershipForItem($item, 'reject');
        $this->checkIfCanChangeItemStatus($item, ['Pending'], 'reject');

        $product = $item->product;
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

    public function ship(Order_item $item, Request $request)
    {
        $this->checkOwnershipForItem($item, 'ship');
        $this->checkIfCanChangeItemStatus($item, ['Preparing'], 'ship');

        $item->update([
            'item_status' => 'Shipped',
        ]);
        $this->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'ship', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been shipped');
    }

    public function deliver(Order_item $item, Request $request)
    {
        $this->checkOwnershipForItem($item, 'deliver');
        $this->checkIfCanChangeItemStatus($item, ['Shipped'], 'deliver');

        $item->update([
            'item_status' => 'Delivered',
        ]);
        $this->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'deliver', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been Delivered');
    }

    public function cancelByStore(Order_item $item, Request $request)
    {
        $this->checkOwnershipForItem($item, 'cancel');
        $this->checkIfCanChangeItemStatus($item, ['Preparing'], 'cancel');

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
