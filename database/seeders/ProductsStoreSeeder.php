<?php

namespace Database\Seeders;

use App\Models\ProductsStores;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsStoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            ProductsStores::factory(10)->create();
        });
    }
}
