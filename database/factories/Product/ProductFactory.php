<?php

namespace Database\Factories\Product;

use App\Models\Category\Category;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'image' => fake()->imageUrl(200, 200),
            'description' => fake()->realText,
            'amount' => fake()->randomDigitNotZero(),
            'price' => fake()->randomDigitNotZero(),
            'store_id' => Store::all()->random()->id,
            'category_id' => Category::all()->random()->id,
        ];
    }
}
