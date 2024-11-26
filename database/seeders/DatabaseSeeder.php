<?php

namespace Database\Seeders;

use Database\Seeders\Store\StoreSeeder;
use Database\Seeders\User\RoleSeeder;
use Database\Seeders\User\UserSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->call([
                RoleSeeder::class,
                UserSeeder::class,
                StoreSeeder::class,
            ]);
        });
    }
}
