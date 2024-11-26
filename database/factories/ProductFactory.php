<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'amount' => fake()->randomDigitNotZero(),
            'price' => fake()->randomDigitNotZero(),
            'image' => fake()->imageUrl(200, 200),
            'description' => fake()->realText,
            'user_id' => User::all()->random()->id,
            'category_id' => Category::all()->random()->id,
            'store_id' => Store::all()->random()->id,
        ];
    }
}
