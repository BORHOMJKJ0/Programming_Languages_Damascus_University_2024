<?php

namespace App\Traits;

use App\Helpers\ResponseHelper;
use App\Models\Product\Product;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ValidationTrait
{
    public function checkAmount(array $data, Product $product)
    {
        if ($data['quantity'] > $product->amount) {
            throw new HttpResponseException(ResponseHelper::jsonResponse([],
                "The quantity must be less or equal than {$product->amount}.",
                400, false));
        }
    }
}
