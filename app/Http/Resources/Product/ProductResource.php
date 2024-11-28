<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Store\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->image
            ? config('app.url').'/storage/'.$this->image
            : null;
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $imageUrl ?? null,
            'isFavorite' => $this->favorites()->where(['user_id' => auth()->id(), 'product_id' => $this->id])->exists() ? 1 : 0,
            'stores' => StoreResource::collection($this->stores)->map(function ($store) {
                return [
                    'store' => $store,
                    'price' => $store->pivot->price ?? null,
                    'amount' => $store->pivot->amount ?? null,
                ];
            }),
            'category' => $this->category->name,
        ];

        if ($request->routeIs('products.show')) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
