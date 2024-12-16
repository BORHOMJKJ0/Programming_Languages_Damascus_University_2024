<?php

namespace App\Http\Controllers\Order\Store;

use App\Http\Controllers\Controller;
use App\Services\OrderService;

class StoreOrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function show($store_id)
    {
        return $this->orderService->getAllStoreOrders($store_id);
    }
}
