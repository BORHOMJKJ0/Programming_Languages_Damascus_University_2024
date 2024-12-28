<?php

namespace Database\Seeders\Role;

use App\Models\User\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'role' => 'guest',
        ]);
        Role::create([
            'role' => 'user',
        ]);
        Role::create([
            'role' => 'admin',
        ]);
    }
}
