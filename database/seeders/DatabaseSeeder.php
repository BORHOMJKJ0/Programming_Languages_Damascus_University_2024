<?php

namespace Database\Seeders;

use Database\Seeders\Cart\CartItemsSeeder;
use Database\Seeders\Cart\CartSeeder;
use Database\Seeders\Category\CategorySeeder;
use Database\Seeders\Image\ImageSeeder;
use Database\Seeders\Product\ProductSeeder;
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
                CategorySeeder::class,
                ProductSeeder::class,
                ImageSeeder::class,
                CartSeeder::class,
                CartItemsSeeder::class,
            ]);
        });
    }
}
