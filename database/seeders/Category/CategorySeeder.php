<?php

namespace Database\Seeders\Category;

use App\Models\Category\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Technology',
            'Fashion',
            'Food and Beverage',
            'Automotive',
            'Health and Beauty',
            'Home and Living',
            'Books and Literature',
            'Entertainment',
            'Sports and Fitness',
            'Travel',
            'Toys and Games',
            'Education',
            'Electronics',
            'Real Estate',
            'Jewelry and Watches',
            'Pets',
            'Finance',
            'Eco-Friendly and Sustainable Products',
            'Baby and Kids',
            'Luxury Goods',
        ];

        DB::transaction(function () use ($categories) {
            foreach ($categories as $categoryName) {
                Category::create(['name' => $categoryName]);
            }
        });
    }
}
