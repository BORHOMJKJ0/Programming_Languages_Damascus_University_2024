<?php

namespace App\Http\Controllers\Order\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\editItemRequest;
use App\Models\Order\Order_item;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function placeOrder(Request $request)
    {
        return $this->orderService->placeOrder($request);
    }

    public function show()
    {
        return $this->orderService->getAllMyOrders();
    }

    public function edit(Order_item $item, editItemRequest $request)
    {
        return $this->orderService->edit($item, $request);
    }

    public function delete(Order_item $item, Request $request)
    {
        return $this->orderService->deleteByCustomer($item, $request);
    }
}
