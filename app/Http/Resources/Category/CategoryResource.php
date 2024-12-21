<?php

namespace App\Http\Resources\Category;

use App\Http\Resources\Product\ProductsNamesResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'en');

        return [
            'id' => $this->id,
            'name' => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'products' => ProductsNamesResource::collection($this->products),
        ];
    }
}
