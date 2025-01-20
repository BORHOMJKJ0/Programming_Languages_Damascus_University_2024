<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Services\OrderService;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function details($order_id)
    {
        return $this->orderService->details($order_id);
    }
}
