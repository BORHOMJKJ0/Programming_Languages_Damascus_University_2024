<?php

namespace Database\Seeders\Auth\User;

use App\Models\User\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Role::factory(10)->create();
        });
    }
}
