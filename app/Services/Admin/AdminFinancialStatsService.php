<?php

namespace App\Services\Admin;

use App\Repositories\Admin\AdminFinancialStatsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminFinancialStatsService
{
    public function __construct(
        private readonly AdminFinancialStatsRepository $adminFinancialStatsRepository,
    ) {}

    public function refreshAfterPaidTestPurchase(int $purchaseId, int $paymentAttemptId, ?string $stripeEventId = null,): void
    {
        $purchase = $this->adminFinancialStatsRepository
            ->findPaidPurchaseForStats($purchaseId);

        if (! $purchase) {
            Log::channel('daily')->info('Paid purchase stats update skipped because purchase was not found as paid', [
                'purchase_id' => $purchaseId,
                'payment_attempt_id' => $paymentAttemptId,
                'stripe_event_id' => $stripeEventId,
            ]);

            return;
        }

        $purchasedAt = CarbonImmutable::parse($purchase->purchased_at);

        $year = (int) $purchasedAt->year;
        $monthNo = (int) $purchasedAt->month;
        $testId = (int) $purchase->test_id;

        DB::transaction(function () use ($year, $monthNo, $testId) {
            $this->adminFinancialStatsRepository
                ->refreshYearlyTestSalesStats(year: $year, testId: $testId);

            $this->adminFinancialStatsRepository
                ->refreshYearlyFinancialMonthStats(year: $year, monthNo: $monthNo);

            $this->adminFinancialStatsRepository
                ->refreshYearlyFinancialStats(year: $year);
        });

        Log::channel('daily')->info('Admin financial summary tables refreshed after paid test purchase', [
            'purchase_id' => $purchaseId,
            'payment_attempt_id' => $paymentAttemptId,
            'stripe_event_id' => $stripeEventId,
            'year' => $year,
            'month_no' => $monthNo,
            'test_id' => $testId,
        ]);
    }

    public function getFinancialStats(int $year): array
    {
        $currentYearStats = $this->adminFinancialStatsRepository->findYearlyFinancialStats($year);
        $previousYearStats = $this->adminFinancialStatsRepository->findYearlyFinancialStats($year - 1);

        $mostPurchasedTestSalesStat = $this->adminFinancialStatsRepository->findMostPurchasedTestSalesStat(
            year: $year,
            testId: $currentYearStats?->most_purchased_test_id
        );

        if ($mostPurchasedTestSalesStat === null) {
            $mostPurchasedTestSalesStat = $this->adminFinancialStatsRepository->findFallbackMostPurchasedTestSalesStat($year);
        }

        return [
            'year' => $year,

            'summary' => [
                'gross_sales_amount' => [
                    'value' => $currentYearStats?->gross_sales_amount,
                ],

                'sold_purchase_count' => [
                    'value' => (int) ($currentYearStats?->sold_purchase_count ?? 0),
                    'change_percentage_from_previous_year' => $this->calculateChangePercentage(
                        current: $currentYearStats?->sold_purchase_count,
                        previous: $previousYearStats?->sold_purchase_count,
                    ),
                ],

                'platform_net_profit_amount' => [
                    'value' => $currentYearStats?->platform_net_profit_amount,
                    'change_percentage_from_previous_year' => $this->calculateChangePercentage(
                        current: $currentYearStats?->platform_net_profit_amount,
                        previous: $previousYearStats?->platform_net_profit_amount,
                    ),
                ],

                'users_profit_amount' => [
                    'value' => $currentYearStats?->users_profit_amount,
                    'change_percentage_from_previous_year' => $this->calculateChangePercentage(
                        current: $currentYearStats?->users_profit_amount,
                        previous: $previousYearStats?->users_profit_amount,
                    ),
                ],

                'average_monthly_platform_profit_amount' => [
                    'value' => $currentYearStats?->average_monthly_platform_profit_amount,
                ],

                'average_monthly_sales_amount' => [
                    'value' => $currentYearStats?->average_monthly_sales_amount,
                ],
            ],

            'top_months_by_sold_purchases' => $this->adminFinancialStatsRepository->getTopMonthsBySoldPurchases($year),

            'top_months_by_platform_profit' => $this->adminFinancialStatsRepository->getTopMonthsByPlatformProfit($year),

            'most_purchased_test' => $mostPurchasedTestSalesStat,
        ];
    }

    private function calculateChangePercentage(mixed $current, mixed $previous): float
    {
        $current = (float) ($current ?? 0);
        $previous = (float) ($previous ?? 0);

        if ($previous <= 0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

}
