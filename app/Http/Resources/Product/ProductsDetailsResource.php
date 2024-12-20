<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mainImage = $this->images->where('main', 1)->first() ?? $this->images->first();
        $imageUrl = $this->getImageUrl($mainImage);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $mainImage ? [
                'id' => $mainImage->id,
                'image' => $imageUrl ?? null,
            ] : null,
            'category' => $this->category->name,
            'description' => $this->description,
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
