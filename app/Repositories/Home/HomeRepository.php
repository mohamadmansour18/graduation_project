<?php

namespace App\Repositories\Home;

use App\Enums\SystemRole;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Helpers\ImageProcessor;
use App\Models\User;
use App\Models\UserSearchHistory;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
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

    ////////////////////////////////////////////////////

    public function interestExists(int $interestId): bool
    {
        return DB::table('interests')
            ->where('id', $interestId)
            ->exists();
    }

    public function paginateTestsByInterest(int $interestId, int $userId , int $perPage): LengthAwarePaginator
    {
        $paginator = DB::table('test as t')
            ->join('test_interset_selections as tis', 'tis.test_id', '=', 't.id')
            ->where('tis.interest_id', $interestId)
            ->where('t.creator_user_id' , '!=' , $userId)
            ->where('t.test_type', TestType::Public->value)
            ->where('t.review_status', TestReviewStatus::Approved->value)
            ->orderByDesc('t.published_at')
            ->select([
                't.id',
                't.title',
                't.description',
                't.question_count',
                't.difficulty_level',
                't.price',
                't.average_rating',
                't.published_at',
            ])
            ->paginate($perPage);

        $testIds = collect($paginator->items())
            ->pluck('id')
            ->toArray();

        if (empty($testIds)) {
            return $paginator;
        }

        $interestsByTestId = DB::table('test_interset_selections as tis')
            ->join('interests as i', 'i.id', '=', 'tis.interest_id')
            ->whereIn('tis.test_id', $testIds)
            ->orderBy('tis.slot_no')
            ->get([
                'tis.test_id',
                'i.id',
                'i.name',
            ])
            ->groupBy('test_id')
            ->map(fn ($items) => $items->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
            ])->values()->toArray());

        $paginator->setCollection(
            collect($paginator->items())->map(function ($test) use ($interestsByTestId) {
                $test->interests = $interestsByTestId[$test->id] ?? [];
                return $test;
            })
        );

        return $paginator;
    }

    public function searchMobileUsers(int $viewerUserId, string $query, int $perPage): CursorPaginator
    {
        $safeSearch = addcslashes(trim($query), '%_\\');

        return User::query()
            ->select([
                'id',
                'name',
                'role_id',
                'is_academically_verified',
            ])
            ->whereHas('role', function ($q) {
                $q->where('name', SystemRole::Mobile_User->value);
            })
            ->where('id', '!=', $viewerUserId)
            ->where('name', 'like', $safeSearch . '%')
            ->with([
                'userProfile:id,user_id,avatar_path,avatar_disk',
                'userOnboardingProfile:id,user_id,education_level',
            ])
            ->withExists([
                'followedUserFollows as viewer_is_following' => function ($q) use ($viewerUserId) {
                    $q->where('follower_user_id', $viewerUserId);
                },
            ])
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function storeSearchQuery(int $userId, string $query): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
    {
        $history = UserSearchHistory::query()
            ->where('user_id', $userId)
            ->where('query', $query)
            ->first();

        if ($history) {
            $history->touch();

            return $history;
        }

        return UserSearchHistory::query()->create([
            'user_id' => $userId,
            'query' => $query,
        ]);
    }

    public function getLatestSearchHistories(int $userId, int $limit = 20): Collection
    {
        return UserSearchHistory::query()
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id', 'query']);
    }

    public function forceDeleteAllHistories(int $userId): int
    {
        return UserSearchHistory::query()
            ->where('user_id', $userId)
            ->forceDelete();
    }

    public function forceDeleteHistoryById(int $userId, int $historyId): int
    {
        return UserSearchHistory::query()
            ->where('user_id', $userId)
            ->whereKey($historyId)
            ->forceDelete();
    }
}
