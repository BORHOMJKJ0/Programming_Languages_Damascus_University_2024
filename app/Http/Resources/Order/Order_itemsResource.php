<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Order_itemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'item_id' => $this->id,
            'product' => $this->product,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'item_status' => $this->item_status,
        ];
    }
}
