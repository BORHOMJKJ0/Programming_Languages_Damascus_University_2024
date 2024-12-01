<?php

namespace Database\Seeders\Cart;

use App\Models\Cart\Cart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Cart::factory(3)->create();
        });
    }
}
