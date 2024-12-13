<?php

namespace Database\Seeders\Image;

use App\Models\Image\Image;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Image::factory(10)->create();
        });
    }
}
