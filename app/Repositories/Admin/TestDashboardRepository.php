<?php

namespace App\Repositories\Admin;

use App\Enums\TestReviewStatus;
use App\Models\Test;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TestDashboardRepository
{
    public function getTestsWhoseCurrentStatusChangedBetween(CarbonInterface $startOfDay, CarbonInterface $endOfDay, array $statuses): Collection
    {
        $statusValues = array_map(
            fn (TestReviewStatus $status) => $status->value,
            $statuses
        );

        $latestStatusHistorySubQuery = DB::table('test_status_histories')
            ->select([
                'test_id',
                DB::raw('MAX(id) as latest_status_history_id'),
            ])
            ->groupBy('test_id');

        return Test::query()
            ->withTrashed()
            ->select('test.*')
            ->joinSub($latestStatusHistorySubQuery, 'latest_status_history', function ($join) {
                $join->on('latest_status_history.test_id', '=', 'test.id');
            })
            ->join('test_status_histories as current_status_history', function ($join) {
                $join->on(
                    'current_status_history.id',
                    '=',
                    'latest_status_history.latest_status_history_id'
                );
            })
            ->whereIn('test.review_status', $statusValues)
            ->whereIn('current_status_history.to_status', $statusValues)
            ->whereColumn('test.review_status', 'current_status_history.to_status')
            ->whereBetween('current_status_history.created_at', [
                $startOfDay,
                $endOfDay,
            ])
            ->with([
                'testIntersetSelections' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_id',
                            'interest_id',
                            'slot_no',
                        ])
                        ->orderBy('slot_no');
                },
                'testIntersetSelections.interest:id,name',
            ])
            ->orderByDesc('current_status_history.created_at')
            ->orderByDesc('test.id')
            ->get();
    }
}
