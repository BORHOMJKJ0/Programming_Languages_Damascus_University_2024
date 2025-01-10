<?php

namespace Database\Seeders\Store;

use App\Models\Store\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Store::factory(10)->create();
        });
    }
}
