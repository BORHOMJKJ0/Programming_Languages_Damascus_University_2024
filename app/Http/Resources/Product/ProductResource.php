<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Store\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'en');
        $mainImage = $this->images->where('main', 1)->first() ?? $this->images->first();
        $imageUrl = $this->getImageUrl($mainImage);
        $data = [
            'id' => $this->id,
            'name' => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'image' => $mainImage ? [
                'id' => $mainImage->id,
                'image' => $imageUrl ?? null,
            ] : null,
            'amount' => $this->amount,
            'price' => $this->price,
            'isFavorite' => $this->isFavorite(),
            'store' => StoreResource::make($this->store),
            'category' => $lang === 'ar' ? $this->category->name_ar : $this->category->name_en,
        ];

        if ($request->routeIs('products.show')) {
            $data['description'] = $lang === 'ar' ? $this->description_ar : $this->description_en;
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
