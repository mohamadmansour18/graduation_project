<?php

namespace App\Repositories\Admin;

use App\Enums\PaymentStatus;
use App\Models\AdminYearlyFinancialMonthStat;
use App\Models\AdminYearlyFinancialStat;
use App\Models\AdminYearlyTestSalesStat;
use App\Models\TestPurchase;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AdminFinancialStatsRepository
{
    public function findPaidPurchaseForStats(int $purchaseId)
    {
        return DB::table('test_purchases')
            ->select([
                'id',
                'test_id',
                'buyer_user_id',
                'seller_user_id',
                'gross_amount',
                'seller_net_amount',
                'platform_fee_amount',
                'payment_status',
                'purchased_at',
            ])
            ->where('id', $purchaseId)
            ->where('payment_status', $this->paidStatus())
            ->whereNotNull('purchased_at')
            ->first();
    }

    public function refreshYearlyFinancialStats(int $year): void
    {
        [$start, $end] = $this->yearRange($year);

        $stats = DB::table('test_purchases')
            ->where('payment_status', $this->paidStatus())
            ->whereBetween('purchased_at', [$start, $end])
            ->selectRaw('COUNT(*) as sold_purchase_count')
            ->selectRaw('COUNT(DISTINCT test_id) as distinct_sold_tests_count')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as gross_sales_amount')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as users_profit_amount')
            ->selectRaw('COALESCE(SUM(platform_fee_amount), 0) as platform_net_profit_amount')
            ->first();

        $mostPurchasedTest = DB::table('test_purchases')
            ->where('payment_status', $this->paidStatus())
            ->whereBetween('purchased_at', [$start, $end])
            ->select('test_id')
            ->selectRaw('COUNT(*) as purchase_count')
            ->groupBy('test_id')
            ->orderByDesc('purchase_count')
            ->orderBy('test_id')
            ->first();

        $now = now();

        DB::table('admin_yearly_financial_stats')->upsert([
            [
                'year' => $year,
                'sold_purchase_count' => (int) $stats->sold_purchase_count,
                'distinct_sold_tests_count' => (int) $stats->distinct_sold_tests_count,
                'gross_sales_amount' => $this->money($stats->gross_sales_amount),
                'users_profit_amount' => $this->money($stats->users_profit_amount),
                'platform_net_profit_amount' => $this->money($stats->platform_net_profit_amount),
                'average_monthly_sales_amount' => $this->divideMoneyBy12($stats->gross_sales_amount),
                'average_monthly_platform_profit_amount' => $this->divideMoneyBy12($stats->platform_net_profit_amount),
                'most_purchased_test_id' => $mostPurchasedTest?->test_id,
                'most_purchased_test_purchase_count' => (int) ($mostPurchasedTest?->purchase_count ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['year'], [
            'sold_purchase_count',
            'distinct_sold_tests_count',
            'gross_sales_amount',
            'users_profit_amount',
            'platform_net_profit_amount',
            'average_monthly_sales_amount',
            'average_monthly_platform_profit_amount',
            'most_purchased_test_id',
            'most_purchased_test_purchase_count',
            'updated_at',
        ]);
    }

    public function refreshYearlyFinancialMonthStats(int $year, int $monthNo): void
    {
        [$start, $end] = $this->monthRange($year, $monthNo);

        $stats = DB::table('test_purchases')
            ->where('payment_status', $this->paidStatus())
            ->whereBetween('purchased_at', [$start, $end])
            ->selectRaw('COUNT(*) as sold_purchase_count')
            ->selectRaw('COUNT(DISTINCT test_id) as distinct_sold_tests_count')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as gross_sales_amount')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as users_profit_amount')
            ->selectRaw('COALESCE(SUM(platform_fee_amount), 0) as platform_net_profit_amount')
            ->first();

        $now = now();

        DB::table('admin_yearly_financial_month_stats')->upsert([
            [
                'year' => $year,
                'month_no' => $monthNo,
                'sold_purchase_count' => (int) $stats->sold_purchase_count,
                'distinct_sold_tests_count' => (int) $stats->distinct_sold_tests_count,
                'gross_sales_amount' => $this->money($stats->gross_sales_amount),
                'users_profit_amount' => $this->money($stats->users_profit_amount),
                'platform_net_profit_amount' => $this->money($stats->platform_net_profit_amount),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['year', 'month_no'], [
            'sold_purchase_count',
            'distinct_sold_tests_count',
            'gross_sales_amount',
            'users_profit_amount',
            'platform_net_profit_amount',
            'updated_at',
        ]);
    }

    public function refreshYearlyTestSalesStats(int $year, int $testId): void
    {
        [$start, $end] = $this->yearRange($year);

        $stats = DB::table('test_purchases')
            ->where('test_id', $testId)
            ->where('payment_status', $this->paidStatus())
            ->whereBetween('purchased_at', [$start, $end])
            ->selectRaw('COUNT(*) as purchase_count')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as gross_sales_amount')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as users_profit_amount')
            ->selectRaw('COALESCE(SUM(platform_fee_amount), 0) as platform_net_profit_amount')
            ->first();

        if ((int) $stats->purchase_count === 0) {
            DB::table('admin_yearly_test_sales_stats')
                ->where('year', $year)
                ->where('test_id', $testId)
                ->delete();

            return;
        }

        $now = now();

        DB::table('admin_yearly_test_sales_stats')->upsert([
            [
                'year' => $year,
                'test_id' => $testId,
                'purchase_count' => (int) $stats->purchase_count,
                'gross_sales_amount' => $this->money($stats->gross_sales_amount),
                'users_profit_amount' => $this->money($stats->seller_net_amount ?? $stats->users_profit_amount),
                'platform_net_profit_amount' => $this->money($stats->platform_net_profit_amount),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['year', 'test_id'], [
            'purchase_count',
            'gross_sales_amount',
            'users_profit_amount',
            'platform_net_profit_amount',
            'updated_at',
        ]);
    }

    private function paidStatus(): string
    {
        return PaymentStatus::Paid->value;
    }

    private function yearRange(int $year): array
    {
        $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0);
        $end = $start->endOfYear();

        return [
            $start->toDateTimeString(),
            $end->toDateTimeString(),
        ];
    }

    private function monthRange(int $year, int $monthNo): array
    {
        $start = CarbonImmutable::create($year, $monthNo, 1, 0, 0, 0);
        $end = $start->endOfMonth();

        return [
            $start->toDateTimeString(),
            $end->toDateTimeString(),
        ];
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function divideMoneyBy12(mixed $amount): string
    {
        if (function_exists('bcdiv')) {
            return bcdiv((string) $amount, '12', 2);
        }

        return number_format(((float) $amount) / 12, 2, '.', '');
    }

    ///////////////////////////////////////////////////////////////

    public function findYearlyFinancialStats(int $year): ?AdminYearlyFinancialStat
    {
        return AdminYearlyFinancialStat::query()
            ->select([
                'year',
                'sold_purchase_count',
                'distinct_sold_tests_count',
                'gross_sales_amount',
                'users_profit_amount',
                'platform_net_profit_amount',
                'average_monthly_sales_amount',
                'average_monthly_platform_profit_amount',
                'most_purchased_test_id',
                'most_purchased_test_purchase_count',
            ])
            ->where('year', $year)
            ->first();
    }

    public function getTopMonthsBySoldPurchases(int $year, int $limit = 3): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_AdminYearlyFinancialMonthStat_C
    {
        return AdminYearlyFinancialMonthStat::query()
            ->select([
                'year',
                'month_no',
                'sold_purchase_count',
                'gross_sales_amount',
                'users_profit_amount',
                'platform_net_profit_amount',
            ])
            ->where('year', $year)
            ->where('sold_purchase_count', '>', 0)
            ->orderByDesc('sold_purchase_count')
            ->orderBy('month_no')
            ->limit($limit)
            ->get();
    }

    public function getTopMonthsByPlatformProfit(int $year, int $limit = 3): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_AdminYearlyFinancialMonthStat_C
    {
        return AdminYearlyFinancialMonthStat::query()
            ->select([
                'year',
                'month_no',
                'sold_purchase_count',
                'gross_sales_amount',
                'users_profit_amount',
                'platform_net_profit_amount',
            ])
            ->where('year', $year)
            ->where('platform_net_profit_amount', '>', 0)
            ->orderByDesc('platform_net_profit_amount')
            ->orderBy('month_no')
            ->limit($limit)
            ->get();
    }

    public function findMostPurchasedTestSalesStat(int $year, ?int $testId): ?AdminYearlyTestSalesStat
    {
        if ($testId === null) {
            return null;
        }

        return AdminYearlyTestSalesStat::query()
            ->select([
                'year',
                'test_id',
                'purchase_count',
                'gross_sales_amount',
                'users_profit_amount',
                'platform_net_profit_amount',
            ])
            ->with([
                'test' => function ($query) {
                    $query->select([
                        'id',
                        'title',
                        'description',
                        'price',
                        'difficulty_level',
                        'question_count',
                        'average_rating',
                        'published_at',
                    ])->with([
                        'testIntersetSelections:id,test_id,interest_id',
                        'testIntersetSelections.interest:id,name',
                    ]);
                },
            ])
            ->where('year', $year)
            ->where('test_id', $testId)
            ->first();
    }

    public function findFallbackMostPurchasedTestSalesStat(int $year): ?AdminYearlyTestSalesStat
    {
        return AdminYearlyTestSalesStat::query()
            ->select([
                'year',
                'test_id',
                'purchase_count',
                'gross_sales_amount',
                'users_profit_amount',
                'platform_net_profit_amount',
            ])
            ->with([
                'test' => function ($query) {
                    $query->select([
                        'id',
                        'title',
                        'description',
                        'price',
                        'difficulty_level',
                        'question_count',
                        'average_rating',
                        'published_at',
                    ])->with([
                        'testIntersetSelections:id,test_id,interest_id',
                        'testIntersetSelections.interest:id,name',
                    ]);
                },
            ])
            ->where('year', $year)
            ->orderByDesc('purchase_count')
            ->orderByDesc('gross_sales_amount')
            ->first();
    }
}
