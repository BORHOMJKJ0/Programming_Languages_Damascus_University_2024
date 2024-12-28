<?php

namespace App\Http\Controllers\Order\Store;

use App\Http\Controllers\Controller;
use App\Models\Order\Order_item;
use App\Models\Store\Store;
use App\Services\OrderService;
use Illuminate\Http\Request;

class StoreOrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function show(Store $store)
    {
        return $this->orderService->getAllStoreOrders($store);
    }

    public function showCompleted(Store $store)
    {
        return $this->orderService->getAllStoreCompletedOrders($store);
    }
    public function accept(Order_item $item, Request $request)
    {
        return $this->orderService->accept($item, $request);
    }

    public function reject(Order_item $item, Request $request)
    {
        return $this->orderService->reject($item, $request);
    }

    public function ship(Order_item $item, Request $request)
    {
        return $this->orderService->ship($item, $request);
    }

    public function deliver(Order_item $item, Request $request)
    {
        return $this->orderService->deliver($item, $request);
    }

    public function cancel(Order_item $item, Request $request)
    {
        return $this->orderService->cancelByStore($item, $request);
    }
}
