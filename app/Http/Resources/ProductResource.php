<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->image
            ? config('app.url').'/storage/'.$this->image
            : null;
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $imageUrl ?? null,
            'price' => (float) $this->price,
            'isFavorite' => $this->favorites()->where(['user_id' => auth()->id(), 'product_id' => $this->id])->exists() ? 1 : 0,
            'user' => $this->user->first_name.' '.$this->user->last_name,
            'category' => $this->category->name,
        ];

        if ($request->routeIs('products.show')) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
