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
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Traits\AuthTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderService
{
    use AuthTrait;

    protected $orderRepository;

    protected $orderItemRepository;

    protected $cartRepository;

    protected $fcmService;

    public function __construct(OrderRepository $orderRepository, OrderItemRepository $orderItemRepository, CartRepository $cartRepository, FcmService $fcmService)
    {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->cartRepository = $cartRepository;
        $this->fcmService = $fcmService;
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $this->checkGuest();

        $cart = $this->cartRepository->getMyCart();
        if ($cart->cart_items->isEmpty()) {
            return ResponseHelper::jsonResponse(
                [],
                'Your cart is empty',
                400,
                false
            );
        }

        $cart_items = $cart->cart_items;
        $order_ids = [];
        foreach ($cart_items as $cart_item) {
            $product = $cart_item->product;
            if (! isset($order_ids[$product->store_id])) {
                $order = $this->orderRepository->createNewOrder([
                    'user_id' => auth()->id(),
                    'store_id' => $product->store_id,
                ]);
                $order_ids[$product->store_id] = $order->id;
            } else {
                $order_id = $order_ids[$product->store_id];
                $order = $this->orderRepository->getOrderById($order_id);
            }
            $order_item = $this->orderItemRepository->createNewItem([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $cart_item->quantity,
                'price' => $product->price * $cart_item->quantity,
            ]);
            $this->orderRepository->updateOrder($order, [
                'total_amount' => $order->total_amount + $order_item->quantity,
                'total_price' => $order->total_price + $order_item->price,
            ]);

            $this->cartRepository->update($cart);
        }
        foreach ($order_ids as $order_id) {
            $order = $this->orderRepository->getOrderById($order_id);
            $this->fcmService->notifyPlaceOrder($order, $request->header('lang', 'en'));
        }

        return ResponseHelper::jsonResponse([], 'The order has been placed');
    }

    public function getAllMyOrders()
    {
        $this->checkGuest();
        $orders = $this->orderRepository->getAllOrdersByUserId();
        $data = [
            'orders' => OrderResource::collection($orders),
        ];

        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

    public function getAllMyCompletedOrders()
    {
        $this->checkGuest();
        $orders = $this->orderRepository->getOrdersArchivedByUserId();
        $data = [
            'orders' => OrderResource::collection($orders),
        ];

        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

    public function getAllStoreOrders(Store $store)
    {
        $this->checkGuest();
        $this->checkOwnership($store, 'Store', 'show orders of ');

        $orders = $this->orderRepository->getAllOrdersByStoreId($store->id);

        $data = [
            'orders' => OrderResource::collection($orders),
        ];

        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

    public function getAllStoreCompletedOrders(Store $store)
    {
        $this->checkGuest();
        $this->checkOwnership($store, 'Store', 'show orders of ');

        $orders = $this->orderRepository->getOrdersArchivedByStoreId($store->id);

        $data = [
            'orders' => OrderResource::collection($orders),
        ];

        return ResponseHelper::jsonResponse($data, 'get orders successfully');
    }

    public function details(Order $order)
    {
        $this->checkGuest();
        $order_details = $this->orderRepository->getOrderDetails($order);
        $data = [
            'order' => OrderResource::make($order),
            'order_details' => Order_itemsResource::collection($order_details),
        ];

        return ResponseHelper::jsonResponse($data, 'get order details successfully');
    }

    public function edit(Order_item $item, editItemRequest $request)
    {
        $this->checkGuest();
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

        $this->orderRepository->updateOrder($item->order, [
            'total_amount' => $item->order->total_amount - $old_quantity + $new_quantity,
            'total_price' => ($item->order->total_price - $item->price) + $new_quantity * $item->product->price,
        ]);
        $this->orderItemRepository->updateItem($item, [
            'quantity' => $new_quantity,
            'price' => $new_quantity * $item->product->price,
        ]);

        //$this->fcmService->notifyStoreItem($item, 'update', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been edited');
    }

    public function deleteByCustomer(Order_item $item, Request $request)
    {
        $this->checkGuest();
        $this->checkOwnership($item->order, 'Order', 'delete an item from');
        $available_status = ['Pending', 'Preparing', 'Not Available', 'Rejected', 'Cancelled'];
        $this->checkIfCanChangeItemStatus($item, $available_status, 'delete');

        $order = $item->order;
        $this->orderRepository->updateOrder($order, [
            'total_amount' => $order->total_amount - $item->quantity,
            'total_price' => $order->total_price - $item->price,
        ]);
        //$this->fcmService->notifyStoreItem($item, 'delete', $request->header('lang', 'en'));
        $item->delete();
        $this->orderRepository->refreshOrderStatus($order);

        return ResponseHelper::jsonResponse([], 'The item has been deleted');
    }

    public function accept(Order_item $item, Request $request)
    {
        $this->checkGuest();
        $this->checkOwnershipForItem($item, 'accept');
        $this->checkIfCanChangeItemStatus($item, ['Pending'], 'accept');

        $product = $item->product;
        if ($item->quantity > $product->amount) {
            $this->orderItemRepository->updateItemStatus($item, 'Not Available');
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
        $this->orderItemRepository->updateItemStatus($item, 'Preparing');

        $this->orderRepository->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'accept', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been accepted');
    }

    public function reject(Order_item $item, Request $request)
    {
        $this->checkGuest();
        $this->checkOwnershipForItem($item, 'reject');
        $this->checkIfCanChangeItemStatus($item, ['Pending'], 'reject');

        $product = $item->product;
        if ($item->quantity > $product->amount) {
            $this->orderItemRepository->updateItemStatus($item, 'Not Available');

            $this->fcmService->notifyCustomerItem($item, 'not available', $request->header('lang', 'en'));

            return ResponseHelper::jsonResponse(
                [],
                'The item has been rejected, the reason is not available quantity',
                200,
                false
            );
        }

        $this->orderItemRepository->updateItemStatus($item, 'Rejected');
        $this->orderRepository->updateOrder($item->order, [
            'total_amount' => $item->order->total_amount - $item->quantity,
            'total_price' => $item->order->total_price - $item->price,
        ]);
        $this->orderRepository->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'reject', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been rejected');
    }

    public function ship(Order_item $item, Request $request)
    {
        $this->checkGuest();
        $this->checkOwnershipForItem($item, 'ship');
        $this->checkIfCanChangeItemStatus($item, ['Preparing'], 'ship');

        $this->orderItemRepository->updateItemStatus($item, 'Shipped');
        $this->orderRepository->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'ship', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been shipped');
    }

    public function deliver(Order_item $item, Request $request)
    {
        $this->checkGuest();
        $this->checkOwnershipForItem($item, 'deliver');
        $this->checkIfCanChangeItemStatus($item, ['Shipped'], 'deliver');

        $this->orderItemRepository->updateItemStatus($item, 'Delivered');
        $this->orderRepository->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'deliver', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been Delivered');
    }

    public function cancelByStore(Order_item $item, Request $request)
    {
        $this->checkGuest();
        $this->checkOwnershipForItem($item, 'cancel');
        $this->checkIfCanChangeItemStatus($item, ['Preparing'], 'cancel');

        $this->orderItemRepository->updateItemStatus($item, 'Cancelled');
        $this->orderRepository->updateOrder($item->order, [
            'total_amount' => $item->order->total_amount - $item->quantity,
            'total_price' => $item->order->total_price - $item->price,
        ]);
        $this->orderRepository->refreshOrderStatus($item->order);

        $this->fcmService->notifyCustomerItem($item, 'cancel', $request->header('lang', 'en'));

        return ResponseHelper::jsonResponse([], 'The item has been Cancelled');
    }
}
