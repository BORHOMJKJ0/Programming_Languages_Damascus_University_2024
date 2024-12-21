<?php

namespace Database\Factories\Store;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    public function definition(): array
    {
        $user = User::doesntHave('store')
            ->where('id', '!=', 11)
            ->inRandomOrder()
            ->first();
        $faker = \Faker\Factory::create('ar_SA');

        return [
            'name_en' => fake()->company(),
            'name_ar' => $faker->company(),
            'image' => fake()->optional()->imageUrl(200, 200),
            'location' => fake()->optional()->address,
            'user_id' => $user->id,
        ];
    }
}
