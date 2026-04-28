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

    /*
    |--------------------------------------------------------------------------
    | Home Cache Keys
    |--------------------------------------------------------------------------
    */
    public const HOME_SCIENTIFIC_INTERESTS_GROUPED = 'home:scientific_interests:grouped';

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

}
