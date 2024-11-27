<?php

namespace Database\Factories\User;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role' => 'user',
        ];
    }
}
