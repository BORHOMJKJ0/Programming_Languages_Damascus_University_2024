<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mainImage = $this->images->where('main', 1)->first() ?? $this->images->first();
        $imageUrl = $this->image
            ? (str_starts_with($this->image, 'https://via.placeholder.com')
                ? $this->image
                : config('app.url').'/storage/'.$this->image)
            : null;

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
}
