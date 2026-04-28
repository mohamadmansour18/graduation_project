<?php

namespace App\Observers;

use App\Models\InterestCategory;
use App\Services\Cache\CacheKeys;

class InterestCategoryObserver
{
    /**
     * Handle the InterestCategory "created" event.
     */
    public function created(InterestCategory $interestCategory): void
    {
        CacheKeys::clearScientificInterests();
    }

    /**
     * Handle the InterestCategory "updated" event.
     */
    public function updated(InterestCategory $interestCategory): void
    {
        CacheKeys::clearScientificInterests();
    }

    /**
     * Handle the InterestCategory "deleted" event.
     */
    public function deleted(InterestCategory $interestCategory): void
    {
        CacheKeys::clearScientificInterests();
    }

    /**
     * Handle the InterestCategory "restored" event.
     */
    public function restored(InterestCategory $interestCategory): void
    {
        CacheKeys::clearScientificInterests();
    }

}
