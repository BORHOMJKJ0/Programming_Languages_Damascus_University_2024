<?php

namespace App\Http\Resources\Cart;

use App\Http\Resources\Product\ProductsNamesResource;
use App\Http\Resources\Store\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'store' => StoreResource::make($this->product->store),
            'product' => ProductsNamesResource::make($this->product),
        ];
    }
}
