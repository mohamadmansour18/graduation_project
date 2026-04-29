<?php

namespace App\Observers;

use App\Models\TestIntersetSelection;
use App\Services\Cache\CacheKeys;

class TestInterestSelectionObserver
{
    /**
     * Handle the TestInterestSelection "created" event.
     */
    public function created(TestIntersetSelection $testInterestSelection): void
    {
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the TestInterestSelection "updated" event.
     */
    public function updated(TestIntersetSelection $testInterestSelection): void
    {
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the TestInterestSelection "deleted" event.
     */
    public function deleted(TestIntersetSelection $testInterestSelection): void
    {
        CacheKeys::clearTestsByInterest();
    }

    /**
     * Handle the TestInterestSelection "restored" event.
     */
    public function restored(TestIntersetSelection $testInterestSelection): void
    {
        //
    }

    /**
     * Handle the TestInterestSelection "force deleted" event.
     */
    public function forceDeleted(TestIntersetSelection $testInterestSelection): void
    {
        //
    }
}
