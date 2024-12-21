<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'en');

        $mainImage = $this->images->where('main', 1)->first() ?? $this->images->first();
        $imageUrl = $this->getImageUrl($mainImage);

        return [
            'id' => $this->id,
            'name' => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'image' => $mainImage ? [
                'id' => $mainImage->id,
                'image' => $imageUrl ?? null,
            ] : null,
            'category' => $lang === 'ar' ? $this->category->name_ar : $this->category->name_en,
            'description' => $lang === 'ar' ? $this->description_ar : $this->description_en,
            'price' => $this->price,
            'amount' => $this->amount,
        ];
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
}
