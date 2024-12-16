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
        $imageUrl = $this->getImageUrl($mainImage);
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $mainImage ? [
                'id' => $mainImage->id,
                'image' => $imageUrl ?? null,
            ] : null,
            'amount' => $this->amount,
            'price' => $this->price,
            'isFavorite' => $this->isFavorite(),
            'store' => StoreResource::make($this->store),
            'category' => $this->category->name,
        ];

        if ($request->routeIs('products.show')) {
            $data['description'] = $this->description;
            unset($data['image']);
            $data['images'] = $this->images->map(function ($image) {

                return [
                    'id' => $image->id,
                    'image' => $this->getImageUrl($image),
                ];
            });
        }

        return $data;
    }

    private function getImageUrl($image): ?string
    {
        if ($image) {
            return str_starts_with($image->image, 'https://via.placeholder.com')
                ? $image->image
                : config('app.url').'/storage/'.$image->image;
        }

        return null;
    }

    private function isFavorite(): int
    {
        return $this->favorites()->where('user_id', auth()->id())->exists() ? 1 : 0;
    }
}
