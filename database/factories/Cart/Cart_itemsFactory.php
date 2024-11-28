<?php

namespace Database\Factories\Cart;

use App\Models\Cart\Cart;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class Cart_itemsFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::has('store')->inRandomOrder()->first();

        return [
            'quantity' => fake()->numberBetween(1, $product->amount),
            'product_id' => $product->id,
            'cart_id' => Cart::all()->random()->id,
        ];
    }
}
