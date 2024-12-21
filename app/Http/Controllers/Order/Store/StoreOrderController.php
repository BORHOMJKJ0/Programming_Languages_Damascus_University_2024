<?php

namespace App\Http\Controllers\Order\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Services\OrderService;

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

    public function accept($item_id)
    {
        return $this->orderService->accept($item_id);
    }

    public function reject($item_id)
    {
        return $this->orderService->reject($item_id);
    }

    public function ship($item_id)
    {
        return $this->orderService->ship($item_id);
    }

    public function deliver($item_id)
    {
        return $this->orderService->deliver($item_id);
    }

    public function cancel($item_id)
    {
        return $this->orderService->cancelByStore($item_id);
    }
}
