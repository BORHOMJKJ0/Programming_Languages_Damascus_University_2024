<?php

namespace Database\Factories\Cart;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::all()->random()->id,
        ];
    }
}
