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
    public const string TAG_HOME = 'home';
    public const string TAG_INTERESTS = 'interests';
    public const string TAG_INTEREST_CATEGORIES = 'interest_categories';
    public const string TAG_TESTS = 'tests';
    public const string TAG_TEST_INTERESTS = 'test_interests';
    public const string TAG_TEST_AI_EVALUATIONS = 'test_ai_evaluations';
    public const string TAG_PROFILE = 'profile';
    public const string TAG_USER_PROFILE = 'user_profile';

    /*
    |--------------------------------------------------------------------------
    | Cache Keys
    |--------------------------------------------------------------------------
    */
    public const string HOME_SCIENTIFIC_INTERESTS_GROUPED = 'home:scientific_interests:grouped';

    public static function testsByInterest(int $interestId, int $page, int $perPage): string
    {
        return "home:interests:{$interestId}:tests:page:{$page}:per_page:{$perPage}";
    }


    public static function testPdfDownloadLock(int $testId): string
    {
        return "tests:{$testId}:pdf_download:lock";
    }

    public static function testAiEvaluation(int $testId, string $contentHash): string
    {
        return "tests:{$testId}:ai_evaluation:{$contentHash}";
    }

    public static function myBasicProfileInfo(int $userId): string
    {
        return "profile:users:{$userId}:basic_info";
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

    public static function testAiEvaluationTags(int $testId): array
    {
        return [
            self::TAG_TESTS,
            self::TAG_TEST_AI_EVALUATIONS,
            "test:{$testId}",
        ];
    }

    public static function myBasicProfileInfoTags(int $userId): array
    {
        return [
            self::TAG_PROFILE,
            self::TAG_USER_PROFILE,
            "user:{$userId}",
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

    public static function clearTestAiEvaluation(int $testId, string $contentHash): void
    {
        Cache::tags(self::testAiEvaluationTags($testId))
            ->forget(self::testAiEvaluation($testId, $contentHash));
    }

    public static function clearTestAiEvaluations(int $testId): void
    {
        Cache::tags(self::testAiEvaluationTags($testId))->flush();
    }

    public static function clearMyBasicProfileInfo(int $userId): void
    {
        Cache::tags(self::myBasicProfileInfoTags($userId))
            ->forget(self::myBasicProfileInfo($userId));
    }

}
