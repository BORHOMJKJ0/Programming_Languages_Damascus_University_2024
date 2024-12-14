<?php

namespace App\Http\Resources\Order;

use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->id,
            'store' => Store::where('id', $this->store_id)->first(),
            'total_price' => $this->total_price,
            'total_amount' => $this->total_amount,
            'order_status' => $this->order_status
        ];
    }
}
