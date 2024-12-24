<?php

namespace App\Http\Resources\Store;

use App\Http\Resources\Product\ProductsDetailsResource;
use App\Http\Resources\User\UserNameResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyStoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $products = $this->products()->paginate($request->query('per_page', 20));
        $lang = $request->header('lang', 'en');
        $imageUrl = $this->image
            ? (str_starts_with($this->image, 'https://via.placeholder.com')
                ? $this->image
                : config('app.url').'/storage/'.$this->image)
            : null;

        return [
            'id' => $this->id,
            'image' => $imageUrl,
            'name' => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'location' => $this->location,
            'user' => UserNameResource::make($this->user),
            'products' => [
                'data' => ProductsDetailsResource::collection($products),
                'pagination' => [
                    'total_pages' => $products->lastPage(),
                    'current_page' => $products->currentPage(),
                    'hasMorePages' => $products->hasMorePages(),
                ],
            ],
        ];
    }
}
