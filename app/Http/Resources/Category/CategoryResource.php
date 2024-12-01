<?php

namespace App\Http\Resources\Category;

use App\Http\Resources\Product\ProductsNamesResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'products' => ProductsNamesResource::collection($this->products),
        ];
    }
}
