<?php

namespace App\Providers;

use App\Events\TestBookmarkStateChanged;
use App\Events\TestDownloaded;
use App\Events\TestLikeStateChanged;
use App\Listeners\UpdateTestBookmarkSummaryTables;
use App\Listeners\UpdateTestDownloadSummaryTables;
use App\Listeners\UpdateTestLikeSummaryTables;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        TestLikeStateChanged::class => [
            UpdateTestLikeSummaryTables::class,
        ],

        TestBookmarkStateChanged::class => [
            UpdateTestBookmarkSummaryTables::class
        ],

        TestDownloaded::class => [
            UpdateTestDownloadSummaryTables::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
