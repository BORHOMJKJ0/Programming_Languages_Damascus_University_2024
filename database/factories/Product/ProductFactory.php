<?php

namespace Database\Factories\Product;

use App\Models\Category\Category;
use App\Models\Product\Product;
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
            ['name_ar' => 'كرسي مكتب مريح', 'description_ar' => 'كرسي بتصميم مريح يدعم العمود الفقري مناسب للعمل الطويل.', 'category' => 'Home and Living'],
            ['name_ar' => 'طاولة طعام خشبية', 'description_ar' => 'طاولة مصنوعة من خشب متين بتصميم أنيق تناسب المطابخ.', 'category' => 'Home and Living'],
            ['name_ar' => 'ماكينة صنع القهوة', 'description_ar' => 'ماكينة حديثة لصنع القهوة بجودة عالية وسهولة استخدام.', 'category' => 'Electronics'],
            ['name_ar' => 'لابتوب للألعاب', 'description_ar' => 'لابتوب عالي الأداء مخصص للألعاب بتقنيات متقدمة.', 'category' => 'Electronics'],
            ['name_ar' => 'مكيف هواء محمول', 'description_ar' => 'مكيف صغير الحجم يمكن نقله بسهولة لتبريد الغرف.', 'category' => 'Electronics'],
            ['name_ar' => 'كريم ترطيب البشرة', 'description_ar' => 'كريم غني بالمرطبات الطبيعية يحافظ على نعومة البشرة.', 'category' => 'Health and Beauty'],
            ['name_ar' => 'عطر رجالي فاخر', 'description_ar' => 'عطر بتوليفة فريدة يدوم طويلاً مناسب للمناسبات.', 'category' => 'Health and Beauty'],
            ['name_ar' => 'شوكولاتة داكنة', 'description_ar' => 'شوكولاتة غنية بمذاق فاخر ومكونات طبيعية.', 'category' => 'Food and Beverage'],
            ['name_ar' => 'شاي أعشاب عضوي', 'description_ar' => 'شاي مهدئ مصنوع من أعشاب طبيعية 100%.', 'category' => 'Food and Beverage'],
            ['name_ar' => 'حذاء رياضي', 'description_ar' => 'حذاء مريح وخفيف مناسب للأنشطة الرياضية والمشي.', 'category' => 'Fashion'],
            ['name_ar' => 'بنطلون جينز', 'description_ar' => 'بنطلون جينز عالي الجودة بتصميم عصري وألوان متنوعة.', 'category' => 'Fashion'],
            ['name_ar' => 'مظلة شمسية كبيرة', 'description_ar' => 'مظلة مقاومة للماء تحمي من أشعة الشمس والرياح.', 'category' => 'Home and Living'],
            ['name_ar' => 'مصباح مكتبي LED', 'description_ar' => 'مصباح موفر للطاقة بتصميم حديث يناسب المكاتب.', 'category' => 'Electronics'],
            ['name_ar' => 'غسالة ملابس أوتوماتيكية', 'description_ar' => 'غسالة بتقنيات حديثة وسعة كبيرة.', 'category' => 'Home and Living'],
            ['name_ar' => 'ميكروويف سريع', 'description_ar' => 'ميكروويف بتقنيات تسخين متقدمة وسهولة في الاستخدام.', 'category' => 'Electronics'],
            ['name_ar' => 'أقلام تلوين للأطفال', 'description_ar' => 'مجموعة من أقلام التلوين الآمنة بألوان زاهية.', 'category' => 'Home and Living'],
            ['name_ar' => 'زيت زيتون عضوي', 'description_ar' => 'زيت عالي الجودة مستخرج من أفضل مزارع الزيتون.', 'category' => 'Food and Beverage'],
            ['name_ar' => 'وسادة طبية للنوم', 'description_ar' => 'وسادة بتصميم يدعم الرقبة والعمود الفقري.', 'category' => 'Home and Living'],
            ['name_ar' => 'فرشاة أسنان كهربائية', 'description_ar' => 'فرشاة متقدمة لتنظيف الأسنان بشكل مثالي.', 'category' => 'Health and Beauty'],
            ['name_ar' => 'سرير أطفال خشبي', 'description_ar' => 'سرير متين وآمن مع تصميم أنيق يناسب الأطفال.', 'category' => 'Home and Living'],
            ['name_ar' => 'كاميرا مراقبة منزلية', 'description_ar' => 'كاميرا ذكية مزودة بخاصية الرؤية الليلية.', 'category' => 'Electronics'],
            ['name_ar' => 'مظلة أطفال ملونة', 'description_ar' => 'مظلة صغيرة بتصميم مرح للأطفال.', 'category' => 'Home and Living'],
            ['name_ar' => 'حقيبة يد نسائية', 'description_ar' => 'حقيبة أنيقة بتصميم عصري وألوان متنوعة.', 'category' => 'Fashion'],
            ['name_ar' => 'ملمع شفاه طبيعي', 'description_ar' => 'ملمع شفاف بتركيبة مرطبة وآمنة.', 'category' => 'Health and Beauty'],
            ['name_ar' => 'كتاب تعليم الطبخ', 'description_ar' => 'كتاب يحتوي على وصفات سهلة وشهية.', 'category' => 'Education'],
            ['name_ar' => 'لعبة ذكاء للأطفال', 'description_ar' => 'لعبة تساعد على تنمية التفكير والإبداع لدى الأطفال.', 'category' => 'Toys and Games'],
            ['name_ar' => 'سجادة صلاة', 'description_ar' => 'سجادة ناعمة بتصميم جميل ومريح.', 'category' => 'Home and Living'],
            ['name_ar' => 'بسكويت الشوفان الصحي', 'description_ar' => 'بسكويت خفيف وصحي غني بالألياف.', 'category' => 'Food and Beverage'],
            ['name_ar' => 'حقيبة سفر متينة', 'description_ar' => 'حقيبة ذات عجلات تسهل التنقل مصنوعة من مواد متينة.', 'category' => 'Fashion'],
            ['name_ar' => 'عصارة برتقال كهربائية', 'description_ar' => 'عصارة سهلة الاستخدام للحصول على عصير طازج.', 'category' => 'Home and Living'],
            ['name_ar' => 'خلاط متعدد الاستخدامات', 'description_ar' => 'خلاط كهربائي قوي لتحضير العصائر والمأكولات.', 'category' => 'Home and Living'],
            ['name_ar' => 'لوحة مفاتيح لاسلكية', 'description_ar' => 'لوحة مفاتيح مريحة تعمل بتقنية البلوتوث.', 'category' => 'Electronics'],
            ['name_ar' => 'كرسي ألعاب مريح', 'description_ar' => 'كرسي مخصص للألعاب بتصميم يدعم الجلوس الطويل.', 'category' => 'Home and Living'],
            ['name_ar' => 'مجموعة أدوات النجارة', 'description_ar' => 'مجموعة شاملة تحتوي على جميع الأدوات الأساسية.', 'category' => 'Home and Living'],
            ['name_ar' => 'مجموعة عناية بالشعر', 'description_ar' => 'مجموعة متكاملة للعناية بالشعر بمكونات طبيعية.', 'category' => 'Health and Beauty'],
            ['name_ar' => 'حذاء نسائي بكعب عالٍ', 'description_ar' => 'حذاء أنيق يناسب المناسبات الرسمية.', 'category' => 'Fashion'],
        ];

        $fakerEn = \Faker\Factory::create();

        $store = Store::inRandomOrder()->first();

        $existingProductNames = Product::where('store_id', $store->id)
            ->distinct()
            ->pluck('name_ar')
            ->toArray();

        $availableProducts = array_filter($products, function ($product) use ($existingProductNames) {
            return ! in_array($product['name_ar'], $existingProductNames);
        });

        $product = $availableProducts[array_rand($availableProducts)];

        $category = Category::where('name_en', $product['category'])->first();

        return [
            'name_en' => $fakerEn->words(3, true),
            'description_en' => $fakerEn->sentence(10),
            'name_ar' => $product['name_ar'],
            'description_ar' => $product['description_ar'],
            'amount' => fake()->numberBetween(1, 100),
            'price' => fake()->randomFloat(2, 10, 1000),
            'store_id' => $store->id,
            'category_id' => $category ? $category->id : Category::first()->id,
        ];
    }
}
