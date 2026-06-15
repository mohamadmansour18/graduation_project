<?php

namespace App\Repositories\Admin;

use App\Models\AdminYearlyLibraryMaterialActivityMonthStat;
use App\Models\AdminYearlyTestActivityMonthStat;
use App\Models\UserStatsByDiscoverySource;
use App\Models\UserStatsSummary;
use Illuminate\Support\Collection;

class HomeRepository
{
    public function getMonthlyStatsByYear(int $year): \Illuminate\Support\Collection
    {
        return AdminYearlyTestActivityMonthStat::query()
            ->select([
                'year',
                'month_no',
                'published_tests_count',
                'likes_count',
                'reviews_count',
                'downloads_count',
            ])
            ->where('year', $year)
            ->orderBy('month_no')
            ->get()
            ->keyBy('month_no');
    }

    public function findUserStatsSummaryByYear(int $year): ?UserStatsSummary
    {
        return UserStatsSummary::query()
            ->select([
                'year',
                'total_completed_mobile_users',
                'male_completed_mobile_users',
                'female_completed_mobile_users',
            ])
            ->where('year', $year)
            ->first();
    }

    public function getDiscoverySourceStatsByYear(int $year): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_UserStatsByDiscoverySource_C
    {
        return UserStatsByDiscoverySource::query()
            ->select([
                'year',
                'discovery_source',
                'completed_mobile_users_count',
            ])
            ->where('year', $year)
            ->orderByDesc('completed_mobile_users_count')
            ->get();
    }

    public function getLibraryMaterialMonthlyActivityByYear(int $year): Collection
    {
        return AdminYearlyLibraryMaterialActivityMonthStat::query()
            ->select([
                'year',
                'month_no',
                'published_materials_count',
                'likes_count',
            ])
            ->where('year', $year)
            ->orderBy('month_no')
            ->get()
            ->keyBy('month_no');
    }

}
