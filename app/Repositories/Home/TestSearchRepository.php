<?php

namespace App\Repositories\Home;

use App\DTOs\Search\TestSearchFilters;
use App\Enums\TestReviewStatus;
use App\Enums\TestSearchScope;
use App\Enums\TestType;
use App\Models\Test;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TestSearchRepository
{
    public function searchTestIds(TestSearchFilters $filters): LengthAwarePaginator
    {
        $builder = Test::search($filters->query)
            ->query(fn ($query) => $query->select('id'));

        $this->applyScopeFilters($builder, $filters);

        return $builder
            ->orderBy('average_rating', 'desc')
            ->orderBy('participants_count', 'desc')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page
            );
    }

    private function applyScopeFilters($builder, TestSearchFilters $filters): void
    {
        if($filters->interestId)
        {
            $builder->where('interest_ids', $filters->interestId);
        }

        match ($filters->scope) {
            TestSearchScope::ALL->value => $this->applyPublicApprovedFilters($builder),

            TestSearchScope::MINE->value => $builder
                ->where('creator_user_id', $filters->userId),

            TestSearchScope::OTHERS->value => $this->applyOthersFilters($builder, $filters->userId),
        };
    }

    private function applyPublicApprovedFilters($builder): void
    {
        $builder
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value);
    }

    private function applyOthersFilters($builder, int $userId): void
    {
        $builder
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->where('creator_user_id', '!=', $userId);
    }

    public function getTestsDetailsByIds(array $testIds): Collection
    {
        if (empty($testIds)) {
            return collect();
        }

        $tests = DB::table('test as t')
            ->whereIn('t.id', $testIds)
            ->select([
                't.id',
                't.title',
                't.description',
                't.published_at',
                't.price',
                't.difficulty_level',
                't.average_rating',
                't.question_count',
            ])
            ->get()
            ->keyBy('id');

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
            ->map(function ($items) {
                return $items->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'name' => $item->name,
                ])->values();
            });

        return collect($testIds)
            ->map(function ($testId) use ($tests, $interestsByTestId) {
                $test = $tests->get($testId);

                if (! $test) {
                    return null;
                }

                $test->interests = $interestsByTestId->get($testId, collect());

                return $test;
            })
            ->filter()
            ->values();
    }
}
