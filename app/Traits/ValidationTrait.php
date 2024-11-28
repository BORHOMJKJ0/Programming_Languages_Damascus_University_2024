<?php

namespace App\Traits;

use App\Helpers\ResponseHelper;
use App\Models\Product\Product;
use App\Models\Store\Store;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ValidationTrait
{
    protected function checkAmount(array $data, Product $product, Store $store)
    {
        $productInStore = $product->stores()->where('store_id', $store->id)->first();

        if (! $productInStore) {
            throw new HttpResponseException(ResponseHelper::jsonResponse([],
                'The product is not available in the specified store.',
                400, false));
        }

        if ($data['quantity'] > $productInStore->pivot->amount) {
            throw new HttpResponseException(ResponseHelper::jsonResponse([],
                "The quantity must be less or equal to {$productInStore->pivot->amount}.",
                400, false));
        }
    }
}
