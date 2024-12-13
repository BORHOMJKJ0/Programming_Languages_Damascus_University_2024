<?php

namespace Database\Factories\Cart;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    public function definition(): array
    {
        $user = User::doesntHave('cart')
            ->inRandomOrder()
            ->first();

        return [
            'user_id' => $user->id,
        ];
    }
}
