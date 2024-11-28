<?php

namespace Database\Factories;

use App\Models\Product\Product;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductsStoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'amount' => fake()->randomDigitNotZero(),
            'price' => fake()->randomDigitNotZero(),
            'store_id' => Store::all()->random()->id,
            'product_id' => Product::all()->random()->id,

        ];
    }
}
