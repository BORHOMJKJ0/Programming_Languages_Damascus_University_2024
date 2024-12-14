<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Store\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mainImage = $this->images->where('main', 1)->first() ?? $this->images->first();
        $imageUrl = $this->image
            ? (str_starts_with($this->image, 'https://via.placeholder.com')
                ? $this->image
                : config('app.url').'/storage/'.$this->image)
            : null;
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $mainImage ? [
                'id' => $mainImage->id,
                'image' => $imageUrl ?? null,
            ] : null,
            'amount' => $this->amount,
            'price' => $this->price,
            'isFavorite' => $this->favorites()->where(['user_id' => auth()->id(), 'product_id' => $this->id])->exists() ? 1 : 0,
            'store' => StoreResource::make($this->store),
            'category' => $this->category->name,
        ];

        if ($request->routeIs('products.show')) {
            $data['description'] = $this->description;
            unset($data['image']);
            $data['images'] = $this->images->map(function ($image) {
                $imageUrl = $this->image
                    ? (str_starts_with($this->image, 'https://via.placeholder.com')
                        ? $this->image
                        : config('app.url').'/storage/'.$this->image)
                    : null;

                return [
                    'id' => $image->id,
                    'image' => $imageUrl,
                ];
            });
        }

        return $data;
    }
}
