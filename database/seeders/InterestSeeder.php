<?php

namespace Database\Seeders;

use App\Models\Interest;
use App\Models\InterestCategory;
use Illuminate\Database\Seeder;

class InterestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $interestsByCategory = [
            'الهندسة والتقنيات' => [
                'علوم الحاسوب',
                'البرمجة',
                'أمن المعلومات',
                'تحليل البيانات',
                'الذكاء الاصطناعي',
                'هندسة الشبكات',
            ],
            'الطب والعلوم الصحية' => [
                'الطب البشري',
                'طب الأسنان',
                'الصيدلة',
                'التمريض',
                'الصحة العامة',
                'التغذية العلاجية',
            ],
            'العلوم الطبيعية' => [
                'الفيزياء',
                'الكيمياء',
                'الأحياء',
                'علوم الأرض',
                'الفلك',
                'الرياضيات التطبيقية',
            ],
            'الأعمال والاقتصاد' => [
                'إدارة الأعمال',
                'المحاسبة',
                'التمويل',
                'التسويق',
                'ريادة الأعمال',
                'الاقتصاد',
            ],
            'التربية والعلوم الإنسانية' => [
                'طرق التدريس',
                'علم النفس',
                'علم الاجتماع',
                'الفلسفة',
                'التاريخ',
                'اللغة العربية',
            ],
            'الفنون والإعلام' => [
                'التصميم الجرافيكي',
                'الإنتاج الإعلامي',
                'الصحافة',
                'التصوير',
                'السينما',
                'الفنون البصرية',
            ],
            'القانون والعلوم السياسية' => [
                'القانون المدني',
                'القانون الجنائي',
                'القانون الدولي',
                'العلاقات الدولية',
                'السياسات العامة',
                'حقوق الإنسان',
            ],
        ];

        $categoryIdsByTitle = InterestCategory::query()
            ->whereIn('title', array_keys($interestsByCategory))
            ->pluck('id', 'title');

        $now = now();
        $interests = [];

        foreach ($interestsByCategory as $categoryTitle => $interestNames) {
            $categoryId = $categoryIdsByTitle->get($categoryTitle);

            if (! $categoryId) {
                continue;
            }

            foreach ($interestNames as $interestName) {
                $interests[] = [
                    'interest_category_id' => $categoryId,
                    'name' => $interestName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Interest::query()->upsert($interests, ['name'], ['interest_category_id', 'updated_at']);
    }
}
