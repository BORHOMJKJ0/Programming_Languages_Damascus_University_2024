<?php

namespace App\Http\Resources\Cart;

use App\Http\Resources\User\UserNameResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paginatedItems = $this->cart_items()->paginate($request->query('per_page', 20));

        return [
            'id' => $this->id,
            'user' => UserNameResource::make($this->user),
            'items' => [
                'data' => CartItemsResource::collection($paginatedItems),
                'pagination' => [
                    'total_pages' => $paginatedItems->lastPage(),
                    'current_page' => $paginatedItems->currentPage(),
                    'hasMorePages' => $paginatedItems->hasMorePages(),
                ],
            ],
        ];
    }
}
