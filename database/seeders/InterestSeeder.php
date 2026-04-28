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
            'اهتمامات علمية أساسية' => [
                'العلوم الأساسية',
                'الرياضيات',
                'التفكير المنطقي',
                'الفيزياء',
                'الكيمياء',
                'الأحياء',
            ],
            'التكنلوجيا والحاسوب' => [
                'علوم الحاسوب',
                'البرمجة',
                'الذكاء الاصطناعي',
                'تحليل البيانات',
                'هندسة البرمجيات',
            ],
            'الهندسة والتقنيات' => [
                'الهندسة الكهربائية',
                'الهندسة الميكانيكية',
                'هندسة العمارة',
                'الهندسة المدنية',
                'الهندسة الطبية',
            ],
            'الطب والصحة' => [
                'العلوم الطبية',
                'التمريض',
                'التشريح',
                'العلوم الصيدلانية',
            ],
        ];

        $categoryColors = [
            'اهتمامات علمية أساسية' => '#5583FF',
            'التكنلوجيا والحاسوب' => '#7D73E8',
            'الهندسة والتقنيات' => '#9463CF',
            'الطب والصحة' => '#A153B5',
        ];

        $interestIcons = [
            'العلوم الأساسية' => 'العلوم-الاساسية.svg',
            'الرياضيات' => 'الرياضيات.svg',
            'التفكير المنطقي' => 'التفكير-المنطقي.svg',
            'الفيزياء' => 'الفيزياء.svg',
            'الكيمياء' => 'الكيمياء.svg',
            'الأحياء' => 'الاحياء.svg',

            'علوم الحاسوب' => 'علوم-الحاسوب.svg',
            'البرمجة' => 'البرمجة.svg',
            'الذكاء الاصطناعي' => 'الذكاء-الاصطناعي.svg',
            'تحليل البيانات' => 'تحليل-البيانات.svg',
            'هندسة البرمجيات' => 'هندسة-البرمجيات.svg',

            'الهندسة الكهربائية' => 'الهندسة-الكهربائية.svg',
            'الهندسة الميكانيكية' => 'الهندسة-الميكانيكية.svg',
            'هندسة العمارة' => 'هندسة-العمارة.svg',
            'الهندسة المدنية' => 'الهندسة-المدنية.svg',
            'الهندسة الطبية' => 'الهندسة-الطبية.svg',

            'العلوم الطبية' => 'العلوم-الطبية.svg',
            'التمريض' => 'التمريض.svg',
            'التشريح' => 'التشريح.svg',
            'العلوم الصيدلانية' => 'العلوم-الصيدلانية.svg',
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
            $color = $categoryColors[$categoryTitle] ?? '#000000';

            foreach ($interestNames as $interestName) {

                $iconFile = $interestIcons[$interestName] ?? 'default.svg';

                $interests[] = [
                    'interest_category_id' => $categoryId,
                    'name' => $interestName,
                    'icon_svg' => 'interest-icons/' . $iconFile,
                    'color' => $color,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Interest::query()->upsert($interests, ['name'], ['interest_category_id', 'icon_svg', 'color', 'updated_at']);
    }
}
