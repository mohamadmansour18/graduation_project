<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class CacheKeys
{
    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    */
    public const TAG_HOME = 'home';
    public const TAG_INTERESTS = 'interests';
    public const TAG_INTEREST_CATEGORIES = 'interest_categories';
    public const TAG_TESTS = 'tests';
    public const TAG_TEST_INTERESTS = 'test_interests';

    /*
    |--------------------------------------------------------------------------
    | Home Cache Keys
    |--------------------------------------------------------------------------
    */
    public const HOME_SCIENTIFIC_INTERESTS_GROUPED = 'home:scientific_interests:grouped';

    public static function testsByInterest(int $interestId, int $page, int $perPage): string
    {
        return "home:interests:{$interestId}:tests:page:{$page}:per_page:{$perPage}";
    }

    /*
    |--------------------------------------------------------------------------
    | Tag Groups
    |--------------------------------------------------------------------------
    */

    public static function scientificInterestsTags(): array
    {
        return [
            self::TAG_HOME,
            self::TAG_INTERESTS,
            self::TAG_INTEREST_CATEGORIES,
        ];
    }

    public static function testsByInterestTags(): array
    {
        return [
            self::TAG_HOME,
            self::TAG_TESTS,
            self::TAG_INTERESTS,
            self::TAG_TEST_INTERESTS,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Helpers
    |--------------------------------------------------------------------------
    */

    public static function clearScientificInterests(): void
    {
        Cache::tags(self::scientificInterestsTags())
            ->forget(self::HOME_SCIENTIFIC_INTERESTS_GROUPED);
    }

    public static function clearTestsByInterest(): void
    {
        Cache::tags(self::testsByInterestTags())->flush();
    }

}
