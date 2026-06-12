<?php

namespace App\Services\Home;

use App\DTOs\Search\TestSearchFilters;
use App\Enums\TestSearchScope;
use App\Enums\TestType;
use App\Helpers\DateProcessor;
use App\Repositories\Home\TestSearchRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class TestSearchService
{
    public function __construct(
        private readonly TestSearchRepository $testSearchRepository
    ) {}

    public function search(TestSearchFilters $filters): LengthAwarePaginator
    {
        //get test ids who searched for from meilisearch
        $idsPaginator = $this->testSearchRepository->searchTestIds($filters);

        $testIds = collect($idsPaginator->items())
            ->pluck('id')
            ->map(fn ($id) => (int)$id)
            ->values()
            ->toArray();

        //get rest of tests data from database
        $tests = $this->testSearchRepository->getTestsDetailsByIds($testIds)->map(fn ($test) => $this->formatTest($test,$filters));

        return new LengthAwarePaginator(
            items: $tests,
            total: $idsPaginator->total(),
            perPage: $idsPaginator->perPage(),
            currentPage: $idsPaginator->currentPage(),
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    private function formatTest(object $test , TestSearchFilters $filters): array
    {
        $data = [
            'id' => (int) $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'interests' => $test->interests ?? [],
            'published_at' => DateProcessor::fromTimestamp($test->published_at),
            'price' => (float) ($test->price ?? 0),
            'difficulty_level' => $test->difficulty_level,
            'average_rating' => (float) ($test->average_rating ?? 0),
            'question_count' => (int) ($test->question_count ?? 0),
        ];

        if ($filters->scope === TestSearchScope::MINE->value) {
            $data['visibility_type'] = $test->test_type;
        }

        return $data;
    }
}
