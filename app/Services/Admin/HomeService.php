<?php

namespace App\Services\Admin;

use App\Enums\DiscoverySource;
use App\Repositories\Admin\HomeRepository;
use Illuminate\Support\Collection;

class HomeService
{
    public function __construct(
        private readonly HomeRepository $repository
    ) {}

    public function getYearlyTestActivity(int $year): array
    {
        $monthlyStats = $this->repository->getMonthlyStatsByYear($year);

        $months = collect(range(1, 12))
            ->map(function (int $monthNo) use ($monthlyStats): array {
                $monthStat = $monthlyStats->get($monthNo);

                return [
                    'month_no' => $monthNo,
                    'published_tests_count' => (int) ($monthStat?->published_tests_count ?? 0),
                    'likes_count' => (int) ($monthStat?->likes_count ?? 0),
                    'reviews_count' => (int) ($monthStat?->reviews_count ?? 0),
                    'downloads_count' => (int) ($monthStat?->downloads_count ?? 0),
                ];
            });

        return [
            'year' => $year,
            'months' => $months,
        ];
    }

    public function getStats(int $year): array
    {
        $userStatsSummary = $this->repository->findUserStatsSummaryByYear($year);

        $totalUsers = (int) ($userStatsSummary?->total_completed_mobile_users ?? 0);
        $maleUsers = (int) ($userStatsSummary?->male_completed_mobile_users ?? 0);
        $femaleUsers = (int) ($userStatsSummary?->female_completed_mobile_users ?? 0);

        $discoverySourceStats = $this->repository->getDiscoverySourceStatsByYear($year);
        $libraryMaterialStatsYear= $this->repository->getLibraryMaterialMonthlyActivityByYear($year);

        return [
            'year' => $year,

            'discovery_sources' => [
                'total_users_count' => $totalUsers,
                'sources' => $this->buildDiscoverySources($discoverySourceStats),
            ],

            'gender' => [
                'total_users_count' => $totalUsers,

                'male' => [
                    'count' => $maleUsers,
                    'percentage' => $this->percentage($maleUsers, $totalUsers),
                ],

                'female' => [
                    'count' => $femaleUsers,
                    'percentage' => $this->percentage($femaleUsers, $totalUsers),
                ],
            ],

            'library_material_yearly_activity' => [
                'totals' => $this->calculateLibraryMaterialTotals($libraryMaterialStatsYear),
                'months' => $this->buildLibraryMaterialMonths($libraryMaterialStatsYear),
            ],
        ];
    }

    private function buildDiscoverySources(Collection $discoverySourceStats): Collection
    {
        $statsBySource = $discoverySourceStats->keyBy(function ($item): string {
            return $item->discovery_source instanceof DiscoverySource
                ? $item->discovery_source->value
                : (string) $item->discovery_source;
        });

        return collect(DiscoverySource::cases())
            ->map(function (DiscoverySource $source) use ($statsBySource): array {
                $stat = $statsBySource->get($source->value);

                return [
                    'key' => $source->name,
                    'label' => $source->value,
                    'users_count' => (int) ($stat?->completed_mobile_users_count ?? 0),
                ];
            })
            ->values();
    }

    private function buildLibraryMaterialMonths(Collection $statsByMonth): Collection
    {
        return collect(range(1, 12))
            ->map(function (int $monthNo) use ($statsByMonth): array {
                $monthStat = $statsByMonth->get($monthNo);

                return [
                    'month_no' => $monthNo,
                    'published_materials_count' => (int) ($monthStat?->published_materials_count ?? 0),
                    'likes_count' => (int) ($monthStat?->likes_count ?? 0),
                ];
            });
    }

    private function calculateLibraryMaterialTotals(Collection $statsByMonth): array
    {
        return [
            'published_materials_count' => (int) $statsByMonth->sum('published_materials_count'),
            'likes_count' => (int) $statsByMonth->sum('likes_count'),
        ];
    }

    private function percentage(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
    }

}
