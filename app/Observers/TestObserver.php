<?php

namespace App\Observers;

use App\Models\Test;
use App\Services\Cache\CacheKeys;

class TestObserver
{
    /**
     * Handle the Test "created" event.
     */
    public function created(Test $test): void
    {
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the Test "updated" event.
     */
    public function updated(Test $test): void
    {
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the Test "deleted" event.
     */
    public function deleted(Test $test): void
    {
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the Test "restored" event.
     */
    public function restored(Test $test): void
    {
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the Test "force deleted" event.
     */
    public function forceDeleted(Test $test): void
    {
        //
    }
}
