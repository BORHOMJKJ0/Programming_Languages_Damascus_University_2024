<?php

namespace Database\Factories\Image;

use App\Models\Image\Image;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    public function definition(): array
    {
        $product_id = Product::all()->random()->id;

        $hasMain = Image::where('product_id', $product_id)
            ->where('main', 1)
            ->exists();

        return [
            'image' => fake()->imageUrl(200, 200),
            'main' => $hasMain ? 0 : 1,
            'product_id' => $product_id,
        ];
    }
}
