<?php

namespace App\Repositories\Admin;

use App\Enums\PaymentStatus;
use App\Models\TestPurchase;
use Carbon\Carbon;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class PaidDashboardRepository
{
    private function salesCurrency(): string
    {
        return strtolower((string) config('payments.pricing_currency', 'syp'));
    }

    public function cursorPaginateSalesHistory(Carbon $startDate, Carbon $endDate, string $sortBy, int $perPage): CursorPaginator
    {
        if ($sortBy === 'test_status') {
            return $this->cursorPaginateSalesHistorySortedByTestStatus(
                startDate: $startDate,
                endDate: $endDate,
                perPage: $perPage,
            );
        }

        $query = TestPurchase::query()
            ->select([
                'test_purchases.id',
                'test_purchases.test_id',
                'test_purchases.buyer_user_id',
                'test_purchases.gross_amount',
                'test_purchases.platform_fee_amount',
                'test_purchases.seller_net_amount',
                'test_purchases.currency',
                'test_purchases.payment_status',
                'test_purchases.purchased_at',
                'test.review_status as test_review_status',
            ])
            ->join('test', 'test.id', '=', 'test_purchases.test_id')
            ->with([
                'buyerUser:id,name',
                'buyerUser.userProfile:user_id,avatar_path,avatar_disk',
            ])
            ->whereBetween('test_purchases.purchased_at', [$startDate, $endDate])
            ->where('test_purchases.currency', $this->salesCurrency())
            ->where('test_purchases.payment_status', PaymentStatus::Paid->value);

        $this->applySalesSorting($query, $sortBy);

        return $query->cursorPaginate($perPage);
    }

    private function applySalesSorting($query, string $sortBy): void
    {
        match ($sortBy) {
            'sale_id' => $query
                ->orderByDesc('test_purchases.id'),

            'gross_amount' => $query
                ->orderByDesc('test_purchases.gross_amount')
                ->orderByDesc('test_purchases.id'),

            'test_id' => $query
                ->orderByDesc('test_purchases.test_id')
                ->orderByDesc('test_purchases.id'),

            'test_status' => $query
                ->orderBy('test_status_order')
                ->orderByDesc('test_purchases.purchased_at')
                ->orderByDesc('test_purchases.id'),

            default => $query
                ->orderByDesc('test_purchases.purchased_at')
                ->orderByDesc('test_purchases.id'),
        };
    }

    public function getSalesStatsForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $stats = TestPurchase::query()
            ->whereBetween('purchased_at', [$startDate, $endDate])
            ->where('currency', $this->salesCurrency())
            ->where('test_purchases.payment_status', PaymentStatus::Paid->value)
            ->selectRaw('
            COUNT(DISTINCT test_id) as distinct_sold_tests_count,
            COALESCE(SUM(gross_amount), 0) as gross_sales_amount,
            COALESCE(SUM(seller_net_amount), 0) as users_profit_amount,
            COALESCE(SUM(platform_fee_amount), 0) as platform_net_profit_amount
        ')
            ->first();

        return [
            'distinct_sold_tests_count' => (int)($stats->distinct_sold_tests_count ?? 0),
            'gross_sales_amount' => round((float)($stats->gross_sales_amount ?? 0), 2),
            'users_profit_amount' => round((float)($stats->users_profit_amount ?? 0), 2),
            'platform_net_profit_amount' => round((float)($stats->platform_net_profit_amount ?? 0), 2),
        ];
    }

    private function cursorPaginateSalesHistorySortedByTestStatus(Carbon $startDate, Carbon $endDate, int $perPage): CursorPaginator
    {
        $statusOrderSubQuery = DB::table('test_purchases')
            ->join('test', 'test.id', '=', 'test_purchases.test_id')
            ->whereBetween('test_purchases.purchased_at', [$startDate, $endDate])
            ->where('test_purchases.currency', $this->salesCurrency())
            ->where('test_purchases.payment_status', PaymentStatus::Paid->value)
            ->select([
                'test_purchases.id as purchase_id',
            ])
            ->selectRaw("
            CASE test.review_status
                WHEN 'مبلغ عنه' THEN 1
                WHEN 'تم حذفه' THEN 2
                WHEN 'تم الموافقة عليه' THEN 3
                WHEN 'يحتاج تعديل' THEN 4
                WHEN 'قيد المراجعة' THEN 5
                ELSE 99
            END as test_status_order
        ");

        return TestPurchase::query()
            ->select([
                'test_purchases.id',
                'test_purchases.test_id',
                'test_purchases.buyer_user_id',
                'test_purchases.gross_amount',
                'test_purchases.platform_fee_amount',
                'test_purchases.seller_net_amount',
                'test_purchases.currency',
                'test_purchases.payment_status',
                'test_purchases.purchased_at',
                'test.review_status as test_review_status',
                'status_order.test_status_order',
            ])
            ->joinSub($statusOrderSubQuery, 'status_order', function ($join) {
                $join->on('status_order.purchase_id', '=', 'test_purchases.id');
            })
            ->join('test', 'test.id', '=', 'test_purchases.test_id')
            ->with([
                'buyerUser:id,name',
                'buyerUser.userProfile:user_id,avatar_path',
            ])
            ->orderBy('status_order.test_status_order')
            ->orderByDesc('test_purchases.purchased_at')
            ->orderByDesc('test_purchases.id')
            ->cursorPaginate($perPage);
    }
}
