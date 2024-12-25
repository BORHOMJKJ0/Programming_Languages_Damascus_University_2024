<?php

namespace App\Http\Controllers\Order\Store;

use App\Http\Controllers\Controller;
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

    public function accept($item_id, Request $request)
    {
        return $this->orderService->accept($item_id, $request);
    }

    public function reject($item_id, Request $request)
    {
        return $this->orderService->reject($item_id, $request);
    }

    public function ship($item_id, Request $request)
    {
        return $this->orderService->ship($item_id, $request);
    }

    public function deliver($item_id, Request $request)
    {
        return $this->orderService->deliver($item_id, $request);
    }

    public function cancel($item_id, Request $request)
    {
        return $this->orderService->cancelByStore($item_id, $request);
    }
}
