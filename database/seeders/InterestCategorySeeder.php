<?php

namespace Database\Seeders;

use App\Models\InterestCategory;
use Illuminate\Database\Seeder;

class InterestCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $categories = collect([
            'الهندسة والتقنيات',
            'الطب والعلوم الصحية',
            'العلوم الطبيعية',
            'الأعمال والاقتصاد',
            'التربية والعلوم الإنسانية',
            'الفنون والإعلام',
            'القانون والعلوم السياسية',
        ])->map(fn(string $title) => [
            'title' => $title,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        InterestCategory::query()->upsert($categories, ['title'], ['updated_at']);
    }
}
