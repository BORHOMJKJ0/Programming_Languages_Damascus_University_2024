<?php

namespace App\Http\Resources\Cart;

use App\Http\Resources\User\UserNameResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => UserNameResource::make($this->user),
            'items' => CartItemsResource::collection($this->cart_items),
        ];
    }
}
