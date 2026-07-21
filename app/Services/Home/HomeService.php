<?php

namespace App\Services\Home;

use App\Exceptions\Api\InterestException;
use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use App\Http\Resources\UserSearchResultResource;
use App\Repositories\Home\HomeRepository;
use App\Services\Cache\CacheKeys;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class HomeService
{
    private const int HOME_CATEGORIES_LIMIT = 8;
    private const int TOP_TEST_CREATORS_LIMIT = 5;

    public function __construct(
        private readonly HomeRepository $homeRepository
    ) {}

    public function getScientificCategoriesForHome(int $userId): array
    {
        $selectedInterests = $this->homeRepository
            ->getUserSelectedInterestsWithTestsCount($userId, self::HOME_CATEGORIES_LIMIT);

        $remainingLimit = self::HOME_CATEGORIES_LIMIT - $selectedInterests->count();

        $excludedInterestIds = $selectedInterests->pluck('id')->toArray();

        $randomInterests = $this->homeRepository
            ->getRandomInterestsWithTestsCount($excludedInterestIds, $remainingLimit);

        return $selectedInterests
            ->merge($randomInterests)
            ->values()
            ->map(function ($interest) {
                return [
                    'id' => $interest->id,
                    'name' => $interest->name,
                    'icon_svg' => ImageProcessor::url($interest->icon_svg),
                    'tests_count' => (int) $interest->tests_count,
                ];
            })
            ->toArray();
    }

    public function getTopTestCreators(int $userId): array
    {
        return $this->homeRepository
            ->getTopTestCreators(self::TOP_TEST_CREATORS_LIMIT , $userId)
            ->map(fn ($creator) => [
                'id' => $creator->id,
                'name' => $creator->name,
                'avatar_url' => ImageProcessor::urlOrDefault($creator->avatar_path ?? null),
                'published_tests_count' => (int) $creator->published_tests_count,
                'average_test_rating' => (float) $creator->average_test_rating,
            ])
            ->values()
            ->toArray();
    }

    //////////////////////////////////////////////////////////////

    public function getScientificInterestsGroupedByCategory(): array
    {
        $categories = Cache::tags(CacheKeys::scientificInterestsTags())
            ->remember(
                CacheKeys::HOME_SCIENTIFIC_INTERESTS_GROUPED,
                now()->addHours(6),
                fn (): array => $this->homeRepository
                    ->getScientificInterestsGroupedByCategory()
                    ->toArray()
            );

        return is_array($categories) ? $categories : [];
    }

    //////////////////////////////////////////////////////////////

    public function getTestsByInterest(int $interestId, int $userId, int $perPage = 10): LengthAwarePaginator
    {
        if (! $this->homeRepository->interestExists($interestId)) {
            throw InterestException::interestNotFound();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();

        /** @var LengthAwarePaginator $paginator */
        $paginator = Cache::tags(CacheKeys::testsByInterestTags())
            ->remember(
                CacheKeys::testsByInterest($interestId, $page, $perPage),
                now()->addMinutes(10),
                fn () => $this->homeRepository->paginateTestsByInterest($interestId , $userId , $perPage)
            );

        $paginator->setCollection(
            $paginator->getCollection()->map(function ($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'description' => $test->description,
                    'interests' => $test->interests ?? [],
                    'question_count' => $test->question_count,
                    'difficulty_level' => $test->difficulty_level,
                    'price' => $test->price ?: 0,
                    'average_rating' => $test->average_rating ,
                    'published_ago' => DateProcessor::fromTimestamp($test->published_at),
                ];
            })
        );

        return $paginator;
    }

    public function searchUsers(int $viewerUserId, string $query, int $perPage): array
    {
        $this->homeRepository->storeSearchQuery(
            userId: $viewerUserId,
            query: $query
        );

        $paginator = $this->homeRepository->searchMobileUsers(
            viewerUserId: $viewerUserId,
            query: $query,
            perPage: $perPage
        );

        return [
            'users' => UserSearchResultResource::collection($paginator->items()),
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => optional($paginator->nextCursor())->encode(),
                'previous_cursor' => optional($paginator->previousCursor())->encode(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];
    }

    public function getSearchHistory(int $userId): array
    {
        $histories = $this->homeRepository->getLatestSearchHistories($userId);

        return [
            'histories' => $histories,
        ];
    }

    public function clearSearchHistory(int $userId): void
    {
        $this->homeRepository->forceDeleteAllHistories($userId);
    }

    public function deleteSearchHistoryItem(int $userId, int $historyId): void
    {
        $this->homeRepository->forceDeleteHistoryById(
            userId: $userId,
            historyId: $historyId
        );
    }
}
