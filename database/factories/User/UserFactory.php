<?php

namespace Database\Factories\User;

use App\Models\User\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_Name' => fake()->firstName,
            'last_Name' => fake()->optional()->lastName,
            'mobile_number' => fake()->unique()->phoneNumber,
            'password' => bcrypt('password'),
            'location' => fake()->optional()->address,
            'image' => fake()->optional()->imageUrl(200, 200),
            'role_id' => Role::all()->random()->id,
        ];
    }
}
