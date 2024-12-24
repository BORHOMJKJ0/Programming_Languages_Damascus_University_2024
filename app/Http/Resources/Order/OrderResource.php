<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\Store\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->id,
            'store' => StoreResource::make($this->store()),
            'total_price' => $this->total_price,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status,
        ];
    }
}
