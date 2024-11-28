<?php

namespace Database\Seeders\Cart;

use App\Models\Cart\Cart_items;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CartItemsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Cart_items::factory(10)->create();
        });
    }
}
