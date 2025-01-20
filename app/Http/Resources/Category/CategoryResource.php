<?php

namespace App\Http\Resources\Category;

use App\Http\Resources\Product\ProductsDetailsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'en');

        $nameColumn = $lang === 'ar' ? 'name_ar' : 'name_en';
        $descriptionColumn = $lang === 'ar' ? 'description_ar' : 'description_en';

        $validColumns = [$nameColumn, 'amount', 'price', $descriptionColumn, 'created_at', 'updated_at'];
        $validDirections = ['asc', 'desc'];

        $orderBy = $request->query('order_by');
        $orderDirection = $request->query('order_direction');

        $isOrderValid = in_array($orderBy, $validColumns) && in_array($orderDirection, $validDirections);

        $query = $this->products();
        if ($isOrderValid) {
            $query->orderBy($orderBy, $orderDirection);
        }

        $products = $query->paginate($request->query('per_page', 20));

        return [
            'id' => $this->id,
            'name' => $lang === 'ar' ? $this->name_ar : $this->name_en,
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
