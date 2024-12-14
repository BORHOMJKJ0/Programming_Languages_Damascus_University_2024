<?php

namespace App\Http\Controllers\Order\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\editItemRequest;
use App\Services\OrderService;

class CustomerOrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function placeOrder()
    {
        return $this->orderService->placeOrder();
    }

    public function show()
    {
        return $this->orderService->getAllMyOrders();
    }

    public function edit($item_id, editItemRequest $request)
    {
        return $this->orderService->edit($item_id, $request);
    }

}
