<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Admin\PaidDashboardRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaidDashboardService
{
    public function __construct(
        private readonly PaidDashboardRepository $paidDashboardRepository
    )
    {}

    public function getSalesHistory(User $owner, array $filters): array
    {
        [$startDate, $endDate] = $this->resolveSalesPeriod(
            period: $filters['period'] ?? 'today',
            startDate: $filters['start_date'] ?? null,
            endDate: $filters['end_date'] ?? null,
        );

        $sortBy = $filters['sort_by'] ?? 'purchased_at';
        $perPage = min((int) ($filters['per_page'] ?? 20), 50);

        $sales = $this->paidDashboardRepository->cursorPaginateSalesHistory(
            startDate: $startDate,
            endDate: $endDate,
            sortBy: $sortBy,
            perPage: $perPage,
        );

        $stats = $this->paidDashboardRepository->getSalesStatsForPeriod(
            startDate: $startDate,
            endDate: $endDate,
        );

        Log::channel('audit')->info('Dashboard sales history viewed', [
            'action' => 'dashboard.sales.history.view',
            'owner_id' => $owner->id,
            'period' => $filters['period'] ?? 'today',
            'sort_by' => $sortBy,
        ]);

        return [
            'period' => [
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
            ],
            'sales' => $sales,
            'stats' => $stats,
        ];
    }

    private function resolveSalesPeriod(string $period, ?string $startDate, ?string $endDate): array
    {
        return match ($period) {
            'week' => [
                now()->subWeek()->startOfDay(),
                now()->endOfDay(),
            ],

            'month' => [
                now()->subMonth()->startOfDay(),
                now()->endOfDay(),
            ],

            'year' => [
                now()->startOfYear(),
                now()->endOfDay(),
            ],

            'custom' => [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ],

            default => [
                now()->startOfDay(),
                now()->endOfDay(),
            ],
        };
    }
}
