<?php

namespace App\Repositories\Home;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Helpers\ImageProcessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HomeRepository
{
    public function getUserSelectedInterestsWithTestsCount(int $userId, int $limit): \Illuminate\Support\Collection
    {
        return DB::table('user_interest_selections as uis')
            ->join('interests as i', 'i.id', '=', 'uis.interest_id')
            ->leftJoinSub($this->approvedPublicTestsCountSubQuery(), 'tc', function ($join) {
                $join->on('tc.interest_id', '=', 'i.id');
            })
            ->where('uis.user_id', $userId)
            ->orderBy('uis.slot_no')
            ->limit($limit)
            ->get([
                'i.id',
                'i.name',
                'i.icon_svg',
                DB::raw('COALESCE(tc.tests_count, 0) as tests_count'),
            ]);
    }

    public function getRandomInterestsWithTestsCount(array $excludedInterestIds, int $limit): \Illuminate\Support\Collection
    {
        return DB::table('interests as i')
            ->leftJoinSub($this->approvedPublicTestsCountSubQuery(), 'tc', function ($join) {
                $join->on('tc.interest_id', '=', 'i.id');
            })
            ->when(!empty($excludedInterestIds), function ($query) use ($excludedInterestIds) {
                $query->whereNotIn('i.id', $excludedInterestIds);
            })
            ->orderByDesc(DB::raw('COALESCE(tc.tests_count, 0)'))
            ->limit($limit)
            ->get([
                'i.id',
                'i.name',
                'i.icon_svg',
                DB::raw('COALESCE(tc.tests_count, 0) as tests_count'),
            ]);
    }

    private function approvedPublicTestsCountSubQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('test_interset_selections as tis')
            ->join('test as t', 't.id', '=', 'tis.test_id')
            ->where('t.test_type', TestType::Public->value)
            ->where('t.review_status', TestReviewStatus::Approved->value)
            ->groupBy('tis.interest_id')
            ->select([
                'tis.interest_id',
                DB::raw('COUNT(DISTINCT t.id) as tests_count'),
            ]);
    }

    public function getTopTestCreators(int $limit): Collection
    {
        return DB::table('user_profile_stats as ups')
            ->join('users as u' , 'u.id' , '=' , 'ups.user_id')
            ->leftJoin('user_profile as up', 'up.user_id', '=', 'u.id')
            ->where('ups.published_tests_count', '>', 0)
            ->orderByDesc('ups.published_tests_count')
            ->orderByDesc('ups.average_test_rating')
            ->limit($limit)
            ->get([
                'u.id',
                'u.name',
                'up.avatar_path',
                DB::raw('COALESCE(ups.published_tests_count, 0) as published_tests_count'),
                DB::raw('COALESCE(ups.average_test_rating, 0) as average_test_rating'),
            ]);
    }

    ////////////////////////////////////////////////////

    public function getScientificInterestsGroupedByCategory(): Collection
    {
        $rows = DB::table('interest_categories as ic')
        ->join('interests as i', 'i.interest_category_id', '=', 'ic.id')
        ->get([
            'ic.id as category_id',
            'ic.title as category_title',

            'i.id as interest_id',
            'i.name as interest_name',
            'i.icon_svg',
            'i.color',
        ]);

        return $rows->groupBy('category_id')
            ->map(function ($items){
                $first = $items->first();

                return [
                    'id' => (int) $first->category_id,
                    'title' => $first->category_title,
                    'interests' => $items->map(fn ($item) => [
                        'id' => (int) $item->interest_id,
                        'name' => $item->interest_name,
                        'icon_svg' => ImageProcessor::urlOrDefault($item->icon_svg ?? null , 'defaults/default-interest-icon.svg'),
                        'icon_color' => $item->color ?? '#5583FF',
                    ])->values()->toArray(),
                ];
            })
            ->values();
    }
}
