<?php

namespace Database\Seeders\User;

use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            User::factory(10)->create();
        });
    }
}
