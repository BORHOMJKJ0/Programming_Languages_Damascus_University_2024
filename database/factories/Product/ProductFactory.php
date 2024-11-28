<?php

namespace Database\Factories\Product;

use App\Models\Category\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'image' => fake()->imageUrl(200, 200),
            'description' => fake()->realText,
            'category_id' => Category::all()->random()->id,
        ];
    }
}
