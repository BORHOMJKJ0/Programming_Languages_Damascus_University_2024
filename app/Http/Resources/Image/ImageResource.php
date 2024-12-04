<?php

namespace App\Http\Resources\Image;

use App\Http\Resources\Product\ProductsDetailsResource;
use App\Http\Resources\User\UserNameResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = config('app.url').'/storage/'.$this->image;

        return [
            'id' => $this->id,
            'image' => $imageUrl,
            'main' => $this->main,
            'product' => ProductsDetailsResource::make($this->product),
            'user' => UserNameResource::make($this->product->store->user),
        ];
    }
}
