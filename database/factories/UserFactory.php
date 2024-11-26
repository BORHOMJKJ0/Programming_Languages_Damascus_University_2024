<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
