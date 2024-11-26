<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'location' => fake()->optional()->address,
            'user_id' => User::all()->random()->id,
        ];
    }
}
