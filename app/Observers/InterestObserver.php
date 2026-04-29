<?php

namespace App\Observers;

use App\Models\Interest;
use App\Services\Cache\CacheKeys;

class InterestObserver
{
    /**
     * Handle the Interest "created" event.
     */
    public function created(Interest $interest): void
    {
        CacheKeys::clearScientificInterests();
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the Interest "updated" event.
     */
    public function updated(Interest $interest): void
    {
        CacheKeys::clearScientificInterests();
    }

    /**
     * Handle the Interest "deleted" event.
     */
    public function deleted(Interest $interest): void
    {
        CacheKeys::clearScientificInterests();
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the Interest "restored" event.
     */
    public function restored(Interest $interest): void
    {
        CacheKeys::clearScientificInterests();
    }

    /**
     * Handle the Interest "force deleted" event.
     */
    public function forceDeleted(Interest $interest): void
    {
        CacheKeys::clearScientificInterests();
    }
}
