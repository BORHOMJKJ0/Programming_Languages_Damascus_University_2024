<?php

namespace Database\Factories\Product;

use App\Models\Category\Category;
use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $products = [
            ['name_ar' => 'تفاح أحمر', 'description_ar' => 'تفاح طازج لذيذ يأتي من أفضل المزارع الطبيعية.', 'category' => 'Food and Beverage'],
            ['name_ar' => 'برتقال صيفي', 'description_ar' => 'برتقال حلو وعصير مليء بالفيتامينات لتعزيز المناعة.', 'category' => 'Food and Beverage'],
            ['name_ar' => 'قميص قطن رجالي', 'description_ar' => 'قميص عالي الجودة مصنوع من القطن 100% مناسب للاستخدام اليومي.', 'category' => 'Fashion'],
            ['name_ar' => 'فستان نسائي أنيق', 'description_ar' => 'فستان أنيق بتصميم عصري يناسب الحفلات والمناسبات.', 'category' => 'Fashion'],
            ['name_ar' => 'مسكن ألم سريع المفعول', 'description_ar' => 'يساعد على تخفيف الآلام بسرعة ويُستخدم تحت إشراف طبي.', 'category' => 'Health and Beauty'],
            ['name_ar' => 'فيتامينات تعزيز المناعة', 'description_ar' => 'مزيج من الفيتامينات والمعادن لدعم صحة الجسم وتعزيز المناعة.', 'category' => 'Health and Beauty'],
            ['name_ar' => 'هاتف ذكي بشاشة كبيرة', 'description_ar' => 'هاتف ذكي بتقنية متقدمة وشاشة فائقة الوضوح لأفضل تجربة استخدام.', 'category' => 'Electronics'],
            ['name_ar' => 'سماعات لاسلكية', 'description_ar' => 'سماعات بتصميم عصري وصوت عالي الجودة تدعم تقنيات البلوتوث.', 'category' => 'Electronics'],
            ['name_ar' => 'حقيبة ظهر مدرسية', 'description_ar' => 'حقيبة عملية مصنوعة من مواد متينة ومناسبة لجميع الأعمار.', 'category' => 'Home and Living'],
            ['name_ar' => 'ساعة يد رياضية', 'description_ar' => 'ساعة مقاومة للماء مع ميزات تتبع اللياقة البدنية وتصميم أنيق.', 'category' => 'Fashion'],
        ];

        $product = $products[array_rand($products)];
        $fakerEn = \Faker\Factory::create();

        $category = Category::where('name_en', $product['category'])->first();

        return [
            'name_en' => $fakerEn->words(3, true),
            'description_en' => $fakerEn->sentence(10),
            'name_ar' => $product['name_ar'],
            'description_ar' => $product['description_ar'],
            'amount' => fake()->numberBetween(1, 100),
            'price' => fake()->randomFloat(2, 10, 1000),
            'store_id' => Store::inRandomOrder()->first()->id,
            'category_id' => $category ? $category->id : Category::first()->id,
        ];
    }
}
