<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->image
            ? config('app.url').'/storage/'.$this->image
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->name,
            'description' => $this->description,
            'price' => $this->price,
            'amount' => $this->amount,
        ];
    }
}
