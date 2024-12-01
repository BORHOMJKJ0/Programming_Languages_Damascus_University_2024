<?php

namespace Database\Seeders\Category;

use App\Models\Category\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Category::factory(10)->create();
        });
    }
}
