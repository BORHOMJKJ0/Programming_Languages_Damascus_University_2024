<?php

namespace Database\Seeders\Product;

use App\Models\Product\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Product::factory(10)->create();
        });
    }
}
