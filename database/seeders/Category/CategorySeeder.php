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
            'Technology' => ['en' => 'Technology', 'ar' => 'تكنولوجيا'],
            'Fashion' => ['en' => 'Fashion', 'ar' => 'أزياء'],
            'Food and Beverage' => ['en' => 'Food and Beverage', 'ar' => 'طعام ومشروبات'],
            'Automotive' => ['en' => 'Automotive', 'ar' => 'السيارات'],
            'Health and Beauty' => ['en' => 'Health and Beauty', 'ar' => 'الصحة والجمال'],
            'Home and Living' => ['en' => 'Home and Living', 'ar' => 'المنزل والمعيشة'],
            'Books and Literature' => ['en' => 'Books and Literature', 'ar' => 'الكتب والأدب'],
            'Entertainment' => ['en' => 'Entertainment', 'ar' => 'الترفيه'],
            'Sports and Fitness' => ['en' => 'Sports and Fitness', 'ar' => 'الرياضة واللياقة'],
            'Travel' => ['en' => 'Travel', 'ar' => 'السفر'],
            'Toys and Games' => ['en' => 'Toys and Games', 'ar' => 'الألعاب واللعب'],
            'Education' => ['en' => 'Education', 'ar' => 'التعليم'],
            'Electronics' => ['en' => 'Electronics', 'ar' => 'الإلكترونيات'],
            'Real Estate' => ['en' => 'Real Estate', 'ar' => 'العقارات'],
            'Jewelry and Watches' => ['en' => 'Jewelry and Watches', 'ar' => 'المجوهرات والساعات'],
            'Pets' => ['en' => 'Pets', 'ar' => 'الحيوانات الأليفة'],
            'Finance' => ['en' => 'Finance', 'ar' => 'المالية'],
            'Eco-Friendly and Sustainable Products' => ['en' => 'Eco-Friendly and Sustainable Products', 'ar' => 'المنتجات الصديقة للبيئة والمستدامة'],
            'Baby and Kids' => ['en' => 'Baby and Kids', 'ar' => 'الأطفال والرضع'],
            'Luxury Goods' => ['en' => 'Luxury Goods', 'ar' => 'السلع الفاخرة'],
        ];

        DB::transaction(function () use ($categories) {
            foreach ($categories as $category) {
                Category::create([
                    'name_en' => $category['en'],
                    'name_ar' => $category['ar'],
                ]);
            }
        });
    }
}
