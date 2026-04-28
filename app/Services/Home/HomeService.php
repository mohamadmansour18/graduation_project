<?php

namespace App\Services\Home;

use App\Helpers\ImageProcessor;
use App\Repositories\Home\HomeRepository;
use App\Services\Cache\CacheKeys;
use Illuminate\Support\Facades\Cache;

class HomeService
{
    private const HOME_CATEGORIES_LIMIT = 8;
    private const TOP_TEST_CREATORS_LIMIT = 5;

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

    public function getTopTestCreators(): array
    {
        return $this->homeRepository
            ->getTopTestCreators(self::TOP_TEST_CREATORS_LIMIT)
            ->map(fn ($creator) => [
                'id' => $creator->id,
                'name' => $creator->name,
                'avatar_url' => ImageProcessor::urlOrDefault($creator->avatar_disk ?? null),
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
}
