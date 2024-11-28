<?php

namespace App\Http\Resources\Store;

use App\Http\Resources\Product\ProductsDetailsResource;
use App\Http\Resources\User\UserNameResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'user' => UserNameResource::make($this->user),
        ];

        if ($request->routeIs('stores.show')) {
            $data['products'] = $this->products->map(function ($product) {
                return [
                    ProductsDetailsResource::make($product),
                ];
            });
        }

        return $data;
    }
}
