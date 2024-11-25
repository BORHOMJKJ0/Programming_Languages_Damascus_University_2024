<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'=>fake()->name,
            'amount'=>fake()->randomDigitNotZero(),
            'price'=>fake()->randomDigitNotZero(),
            'image' => fake()->imageUrl(200, 200),
            'user_id' => User::all()->random()->id,
            'category_id' => Category::all()->random()->id,
        ];
    }
}
